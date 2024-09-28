<?php

declare(strict_types=1);

namespace App\Endpoint\Temporal\Workflow;

use App\Application\Temporal\ExceptionHelper;
use App\Endpoint\Temporal\Activity\NotificationServiceActivity;
use App\Endpoint\Temporal\Activity\PaymentServiceActivity;
use App\Endpoint\Temporal\Activity\TaxiRequestActivity;
use App\Endpoint\Temporal\Workflow\DTO\AcceptRequest;
use App\Endpoint\Temporal\Workflow\DTO\BlockFundsRequest;
use App\Endpoint\Temporal\Workflow\DTO\CancelRequest;
use App\Endpoint\Temporal\Workflow\DTO\CreateRequest;
use App\Endpoint\Temporal\Workflow\DTO\RefundRequest;
use App\Endpoint\Temporal\Workflow\DTO\StartTripRequest;
use Carbon\CarbonInterval;
use Payment\Exception\InsufficientFundsException;
use Ramsey\Uuid\UuidInterface;
use Spiral\TemporalBridge\Attribute\AssignWorker;
use Taxi\Exception\DriverAlreadyAssignedException;
use Taxi\Exception\TaxiRequestCancelledException;
use Taxi\TaxiRequest;
use Temporal\Activity\ActivityOptions;
use Temporal\Common\IdReusePolicy;
use Temporal\Common\RetryOptions;
use Temporal\Exception\Failure\ActivityFailure;
use Temporal\Exception\Failure\CanceledFailure;
use Temporal\Internal\Workflow\ActivityProxy;
use Temporal\Promise;
use Temporal\Workflow;
use Temporal\Workflow\ChildWorkflowOptions;

#[AssignWorker(TaskQueue::TAXI_SERVICE)]
#[Workflow\WorkflowInterface]
final class TaxiRequestWorkflow
{
    private ActivityProxy|TaxiRequestActivity $taxiOrdering;
    private ActivityProxy|PaymentServiceActivity $paymentService;
    private ActivityProxy|NotificationServiceActivity $notificationService;

    private ?TaxiRequest $taxiRequest = null;

    public function __construct()
    {
        $this->taxiOrdering = Workflow::newActivityStub(
            TaxiRequestActivity::class,
            ActivityOptions::new()
                ->withStartToCloseTimeout(CarbonInterval::minute())
                ->withTaskQueue(TaskQueue::TAXI_SERVICE)
                ->withRetryOptions(
                    RetryOptions::new()
                        ->withMaximumAttempts(2)
                        ->withBackoffCoefficient(1.5),
                ),
        );

        $this->paymentService = Workflow::newActivityStub(
            PaymentServiceActivity::class,
            ActivityOptions::new()
                ->withRetryOptions(
                    RetryOptions::new()
                        // We can specify the exceptions that should not be retried
                        // For example, if the user doesn't have enough money
                        ->withNonRetryableExceptions([InsufficientFundsException::class])
                        ->withMaximumAttempts(3)
                        ->withInitialInterval(CarbonInterval::seconds(5))
                        ->withBackoffCoefficient(1.5),
                )
                ->withStartToCloseTimeout(CarbonInterval::minutes(5))
                ->withTaskQueue(TaskQueue::PAYMENT_SERVICE),
        );

        $this->notificationService = Workflow::newActivityStub(
            NotificationServiceActivity::class,
            ActivityOptions::new()
                ->withStartToCloseTimeout(CarbonInterval::minutes(5))
                ->withRetryOptions(
                    RetryOptions::new()
                        ->withMaximumAttempts(2)
                        ->withBackoffCoefficient(1.5),
                )
                ->withTaskQueue(TaskQueue::NOTIFICATION_SERVICE),
        );
    }

    private ?AcceptRequest $acceptRequest = null;
    private ?CancelRequest $cancelRequest = null;

    #[Workflow\WorkflowMethod]
    public function createRequest(CreateRequest $request)
    {
        // First, we need to create a taxi request
        $this->taxiRequest = yield $this->taxiOrdering->requestTaxi($request);
        \assert($this->taxiRequest instanceof TaxiRequest);

        $requestSaga = new Workflow\Saga();
        $requestSaga->setParallelCompensation(parallelCompensation: true);

        /** @var UuidInterface $operationUuid */
        $operationUuid = yield Workflow::uuid7();

        try {
            // Then we need to block money on user account for the ride
            /** @var UuidInterface $transactionUuid */
            $transactionUuid = yield $this->paymentService->blockFunds(
                new BlockFundsRequest(
                    operationUuid: $operationUuid, // For idempotency
                    userUuid: $request->userUuid,
                    amount: $this->taxiRequest->estimatedPrice,
                ),
            );
        } catch (ActivityFailure $e) {
            // 1. If user doesn't have enough money, we can notify the user and cancel the request
            if (ExceptionHelper::findException($e, InsufficientFundsException::class)) {
                // Notify the user that he doesn't have enough money
                yield $this->notificationService->insufficientFunds($this->taxiRequest->uuid);

                throw new CanceledFailure('Insufficient funds');
            }

            throw new CanceledFailure('Something went wrong with the payment. Please try again later');
        }

        // If something goes wrong, we need to refund the money
        $requestSaga->addCompensation(fn() => $this->paymentService->refundFunds(
            new RefundRequest(
                operationUuid: $operationUuid, // For idempotency
                userUuid: $request->userUuid,
                transactionUuid: $transactionUuid,
            ),
        ));

        // Notify drivers about the new request
        yield $this->notificationService->newRequest($this->taxiRequest->uuid);

        $assignmentAttempts = 0;
        $maxAssignmentAttempts = 5;

        try {
            https://temporal.io

            // Wait for driver to accept the request
            while ($assignmentAttempts < $maxAssignmentAttempts) {
                // Wait for 5 minutes for the event to happen
                // If the event happens earlier, the function will return immediately
                // If the event doesn't happen in 5 minutes, the function will return false
                /** @var bool $isAssigned */
                $isAssigned = yield Workflow::awaitWithTimeout(
                    CarbonInterval::minute(),
                    fn(): bool => $this->driverAssigned(),
                );

                if (!$isAssigned) {
                    // Notify every minute that we are still searching for the driver. Max 5 attempts (5 minutes)
                    $assignmentAttempts++;
                    if ($assignmentAttempts < $maxAssignmentAttempts) {
                        yield $this->notificationService->stillSearching($request->requestUuid);
                        continue;
                    }

                    // Scenario to handle:
                    // 1. Assign the request to the nearest available driver
                    // 2. Probably the price is too low, we can increase the price and notify the user
                    //    and go to the next iteration
                    // 3. If no driver accepted the request, we can cancel the request

                    // Notify the user that no driver is available
                    yield $this->notificationService->noDriverAvailable($request->requestUuid);
                    throw new TaxiRequestCancelledException('No driver accepted the request');
                }

                // Validate the driver
                $driverIsMatched = yield $this->taxiOrdering->validateDriver(
                    $this->taxiRequest->uuid,
                    $this->acceptRequest,
                );

                // If the driver is not matched, we can notify the user and go to the next iteration
                if (!$driverIsMatched->isMatched) {
                    // Notify the user that the driver is not available
                    yield $this->notificationService->driverMatchFailed(
                        taxiRequestUuid: $this->taxiRequest->uuid,
                        driverUuid: $this->acceptRequest->driverUuid,
                    );

                    $this->acceptRequest = null;
                    continue;
                }

                // Assign the driver to the request
                $this->taxiRequest = yield $this->taxiOrdering->assignDriver(
                    $this->taxiRequest->uuid,
                    $this->acceptRequest->driverUuid,
                );

                // Notify the user that the driver accepted the request
                yield $this->notificationService->driverAccepted(
                    $this->taxiRequest->userUuid,
                    $this->acceptRequest->driverUuid,
                );

                break;
            }
        } catch (\Throwable $e) {
            if (ExceptionHelper::shouldBeCanceled($e)) {
                yield $this->compensate($e, $requestSaga);

                yield $this->taxiOrdering->cancelRequest(
                    $this->taxiRequest->uuid,
                    $e->getMessage(),
                );

                return;
            }

            goto https;
        }

        // If the driver accepted the request, we can start the trip
        yield Workflow::newChildWorkflowStub(
            class: TripWorkflow::class,
            options: ChildWorkflowOptions::new()
                ->withWorkflowId(workflowId: $this->taxiRequest->uuid . '-trip')
                // Disallow duplicate workflows with the same ID.
                ->withWorkflowIdReusePolicy(policy: IdReusePolicy::AllowDuplicateFailedOnly)
                ->withTaskQueue(taskQueue: TaskQueue::TAXI_SERVICE),
        )->start(new StartTripRequest($this->taxiRequest->uuid));

        yield Promise::all([
            // Charge user for the trip
            $this->paymentService->commitFunds($transactionUuid),

            // Notify user about the trip finish and ask to rate each other
            $this->notificationService->tripFinished($this->taxiRequest->uuid),
        ]);
    }

    #[Workflow\UpdateMethod]
    public function cancelRequest(CancelRequest $request): void
    {
        $this->cancelRequest = $request;
    }

    #[Workflow\UpdateMethod]
    public function acceptRequest(AcceptRequest $request): void
    {
        $this->acceptRequest = $request;
    }

    #[Workflow\UpdateValidatorMethod('acceptRequest')]
    public function validateDriver(AcceptRequest $request): void
    {
        if ($this->acceptRequest !== null) {
            throw new DriverAlreadyAssignedException();
        }
    }

    #[Workflow\QueryMethod]
    public function getTaxiRequestStatus(): string
    {
        return $this->taxiRequest?->status->value ?? 'unknown';
    }

    private function compensate(\Throwable $e, Workflow\Saga $requestSaga): \Generator
    {
        // Run compensation for the request in a detached way to avoid activities
        // cancellation in case of the workflow finishing

        // Parallel activities execution
        yield Workflow::asyncDetached(function () use ($requestSaga, $e) {
            yield Promise::all([
                // Return money to the user
                $requestSaga->compensate(),

                // Notify the user that the request was canceled
                $this->notificationService->userCanceled($this->taxiRequest->uuid),

                // Cancel the request
                $this->taxiOrdering->cancelRequest(
                    $this->taxiRequest->uuid,
                    $e->getMessage(),
                ),
            ]);
        });
    }

    private function checkRequestStatus(): void
    {
        if ($this->cancelRequest) {
            throw new TaxiRequestCancelledException($this->cancelRequest->reason);
        }
    }

    /**
     * @throws TaxiRequestCancelledException
     */
    private function driverAssigned(): bool
    {
        $this->checkRequestStatus();

        return $this->acceptRequest !== null;
    }
}
