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
use App\Endpoint\Temporal\Workflow\DTO\DriverRateRequest;
use App\Endpoint\Temporal\Workflow\DTO\RefundRequest;
use App\Endpoint\Temporal\Workflow\DTO\StartTripRequest;
use App\Endpoint\Temporal\Workflow\DTO\UserRateRequest;
use Carbon\CarbonInterval;
use Spiral\TemporalBridge\Attribute\AssignWorker;
use Taxi\Exception\TaxiRequestCancelledException;
use Taxi\TaxiRequest;
use Taxi\TaxiRequestStatus;
use Taxi\Trip;
use Temporal\Activity\ActivityOptions;
use Temporal\Common\IdReusePolicy;
use Temporal\Common\RetryOptions;
use Temporal\Exception\Failure\ActivityFailure;
use Temporal\Internal\Workflow\ActivityProxy;
use Temporal\Promise;
use Temporal\Workflow;
use Temporal\Workflow\ChildWorkflowOptions;

#[AssignWorker('taxi-service')]
#[Workflow\WorkflowInterface]
final class TaxiRequestWorkflow
{
    private ActivityProxy|TaxiRequestActivity $taxiOrdering;
    private ActivityProxy|PaymentServiceActivity $paymentService;
    private ActivityProxy|NotificationServiceActivity $notificationService;

    /** @var CancelRequest[]|AcceptRequest[] */
    private array $queue = [];

    private ?TaxiRequest $taxiRequest = null;
    private ?Trip $trip = null;

    public function __construct()
    {
        $this->taxiOrdering = Workflow::newActivityStub(
            TaxiRequestActivity::class,
            ActivityOptions::new()
                ->withStartToCloseTimeout(CarbonInterval::minute())
                ->withTaskQueue('taxi-service'),
        );

        $this->paymentService = Workflow::newActivityStub(
            PaymentServiceActivity::class,
            ActivityOptions::new()
                ->withRetryOptions(
                    RetryOptions::new()
                        // We can specify the exceptions that should not be retried
                        // For example, if server problems are not retryable
                        // ->withNonRetryableExceptions(\InvalidArgumentException::class)
                        ->withMaximumAttempts(3)
                        ->withBackoffCoefficient(1.5),
                )
                ->withStartToCloseTimeout(CarbonInterval::minutes(5))
                ->withTaskQueue('payment-service'),
        );

        $this->notificationService = Workflow::newActivityStub(
            NotificationServiceActivity::class,
            ActivityOptions::new()
                ->withStartToCloseTimeout(CarbonInterval::minutes(5))
                ->withTaskQueue('payment-service'),
        );
    }

    private ?AcceptRequest $acceptRequest = null;

    /**
     * @throws TaxiRequestCacnelledException
     */
    private function driverAssigned(): bool
    {
        $this->checkRequestStatus();

        return $this->acceptRequest !== null;
    }

    private ?CancelRequest $cancelRequest = null;

    private function checkRequestStatus(): void
    {
        if ($this->cancelRequest) {
            throw new TaxiRequestCacnelledException($this->cancelRequest->reason);
        }
    }

    #[Workflow\WorkflowMethod]
    public function createRequest(CreateRequest $request)
    {
        // First, we need to create a taxi request
        $this->taxiRequest = yield $this->taxiOrdering->requestTaxi($request);

        $requestSaga = new Workflow\Saga();
        $requestSaga->setParallelCompensation(true);

        $operationUuid = yield Workflow::uuid7();

        // Then we need to block money on user account for the ride
        $transactionUuid = yield $this->paymentService->blockFunds(
            new BlockFundsRequest(
                operationUuid: $operationUuid, // For idempotency
                userUuid: $request->userUuid,
                amount: $this->taxiRequest->estimatedPrice,
            ),
        );

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

        try {
            assignDriver:

            // Wait for driver to accept the request
            while (true) {
                // Wait for 5 minutes for the event to happen
                // If the event happens earlier, the function will return immediately
                // If the event doesn't happen in 5 minutes, the function will return false
                $isAssigned = yield Workflow::awaitWithTimeout(
                    CarbonInterval::minutes(5),
                    fn(): bool => $this->driverAssigned(),
                );

                // If 5 minutes passed and no driver accepted the request
                if (!$isAssigned) {
                    // Scenario to handle:
                    // 1. Assign the request to the nearest available driver
                    // 2. Probably the price is too low, we can increase the price and notify the user
                    //    and go to the next iteration
                    // 3. If no driver accepted the request, we can cancel the request

                    // Notify the user that no driver is available
                    yield $this->notificationService->noDriverAvailable($request->requestUuid);
                    throw new TaxiRequestCancelledException('No driver accepted the request');
                }

                $this->taxiRequest = yield $this->taxiOrdering->assignDriver(
                    $this->taxiRequest->uuid,
                    $this->acceptRequest->driverUuid,
                );

                yield $this->notificationService->driverAccepted(
                    $this->taxiRequest->userUuid,
                    $this->acceptRequest->driverUuid,
                );

                break;
            }
        } catch (\Throwable $e) {
            if (ExceptionHelper::shouldBeCompensated($e)) {
                // Parallel activities execution
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

                return;
            }

            goto assignDriver;
        }

        // If the driver accepted the request, we can start the trip
        $this->trip = yield Workflow::newChildWorkflowStub(
            TripWorkflow::class,
            ChildWorkflowOptions::new()
                ->withWorkflowId($this->taxiRequest->uuid . '-trip')
                // Disallow duplicate workflows with the same ID.
                ->withWorkflowIdReusePolicy(IdReusePolicy::AllowDuplicateFailedOnly)
                ->withTaskQueue('taxi-service'),
        )->start(new StartTripRequest($this->taxiRequest->uuid));

        yield Promise::all([
            // Charge user for the trip
            $this->paymentService->commitFunds($transactionUuid),

            // Notify user about the trip finish and ask to rate each other
            $this->notificationService->tripFinished($this->taxiRequest->uuid),
        ]);

        // If some of the parties haven't rated each other we can give them some time
        // to rate each other
        while ($this->trip->driverRatingUuid === null || $this->trip->userRatingUuid === null) {
            $isEvent = yield Workflow::awaitWithTimeout(
                CarbonInterval::hour(),
                fn(): bool => $this->queue !== [],
            );

            // No one rated the other
            if (!$isEvent) {
                break;
            }

            foreach ($this->queue as $request) {
                if ($request instanceof UserRateRequest) {
                    $this->trip = yield $this->taxiOrdering->rateUser($this->trip->uuid, $request);
                } elseif ($request instanceof DriverRateRequest) {
                    $this->trip = yield $this->taxiOrdering->rateDriver($this->trip->uuid, $request);
                }
            }
        }
    }

    #[Workflow\UpdateMethod]
    public function cancelRequest(CancelRequest $request): void
    {
        $this->cancelRequest = $request;
    }

    #[Workflow\SignalMethod]
    public function rateUser(UserRateRequest $request): void
    {
        $this->queue[] = $request;
    }

    #[Workflow\SignalMethod]
    public function rateDriver(DriverRateRequest $request): void
    {
        $this->queue[] = $request;
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
        return $this->taxiRequest->status->value;
    }
}
