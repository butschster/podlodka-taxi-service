<?php

declare(strict_types=1);

namespace App\Endpoint\Temporal\Workflow;

use App\Endpoint\Temporal\Activity\NotificationServiceActivity;
use App\Endpoint\Temporal\Activity\PaymentServiceActivity;
use App\Endpoint\Temporal\Activity\TaxiRequestActivity;
use App\Endpoint\Temporal\Workflow\DTO\AcceptRequest;
use App\Endpoint\Temporal\Workflow\DTO\BlockFundsRequest;
use App\Endpoint\Temporal\Workflow\DTO\CancelRequest;
use App\Endpoint\Temporal\Workflow\DTO\CreateRequest;
use App\Endpoint\Temporal\Workflow\DTO\DriverRateRequest;
use App\Endpoint\Temporal\Workflow\DTO\RefundRequest;
use App\Endpoint\Temporal\Workflow\DTO\UserRateRequest;
use Carbon\CarbonInterval;
use Spiral\TemporalBridge\Attribute\AssignWorker;
use Taxi\Rating;
use Taxi\TaxiRequest;
use Taxi\Trip;
use Temporal\Activity\ActivityOptions;
use Temporal\Common\IdReusePolicy;
use Temporal\Common\RetryOptions;
use Temporal\Internal\Workflow\ActivityProxy;
use Temporal\Workflow;
use Temporal\Workflow\ChildWorkflowOptions;
use Temporal\Workflow\WorkflowInterface;
use Temporal\Workflow\WorkflowMethod;

#[AssignWorker('taxi-service')]
#[WorkflowInterface]
final class TaxiRequestWorkflow
{
    private ActivityProxy|TaxiRequestActivity $taxiOrdering;
    private ActivityProxy|PaymentServiceActivity $paymentService;
    private ActivityProxy|NotificationServiceActivity $notificationService;

    /**
     * @var CancelRequest[]|AcceptRequest[]
     */
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

    #[WorkflowMethod]
    public function createRequest(CreateRequest $request)
    {
        $this->taxiRequest = yield $this->taxiOrdering->requestTaxi($request);

        $requestSaga = new Workflow\Saga();
        // Configure SAGA to run compensation activities in parallel
        $requestSaga->setParallelCompensation(true);

        // Block money on user account for the ride
        $transactionUuid = yield $this->paymentService->blockFunds(
            new BlockFundsRequest(
                userUuid: $request->userUuid,
                amount: $this->taxiRequest->estimatedPrice,
            ),
        );

        // Add compensation activity to the SAGA
        $requestSaga->addCompensation(
            $this->paymentService->refundFunds(
                new RefundRequest(
                    userUuid: $request->userUuid,
                    transactionUuid: $transactionUuid,
                ),
            ),
        );

        // Notify drivers about the new request
        yield $this->notificationService->newRequest($this->taxiRequest->uuid);

        $isCanceled = false;
        $cancelReason = 'Some error happened';

        // Wait for driver to accept the request
        while (true) {
            // Wait for 5 minutes for the event to happen
            // If the event happens earlier, the function will return immediately
            // If the event doesn't happen in 5 minutes, the function will return false
            $isEvent = yield Workflow::awaitWithTimeout(
                CarbonInterval::minutes(5),
                fn(): bool => $this->queue !== [],
            );

            // If 5 minutes passed and no driver accepted the request
            if (!$isEvent) {
                yield $this->notificationService->noDriverAvailable($request->userUuid);
                $isCanceled = true;
                $cancelReason = 'No driver accepted the request';
                break;
            }

            // Process all the events in the queue
            while ($this->queue !== []) {
                $request = \array_shift($this->queue);

                // User canceled the request
                if ($request instanceof CancelRequest) {
                    yield $this->notificationService->userCanceled($this->taxiRequest->uuid);

                    $isCanceled = true;
                    $cancelReason = $request->reason;
                    break;
                }

                if ($request instanceof AcceptRequest) {
                    $result = yield $this->taxiOrdering->validateDriver($this->taxiRequest->uuid, $request);
                    if ($result->isMatched) {
                        $this->taxiRequest = yield $this->taxiOrdering->assignDriver(
                            $this->taxiRequest->uuid,
                            $request->driverUuid,
                        );

                        yield $this->notificationService->driverAccepted(
                            $this->taxiRequest->user->uuid,
                            $request->driverUuid,
                        );

                        break;
                    } else {
                        yield $this->notificationService->driverMatchFailed(
                            $this->taxiRequest->user->uuid,
                            $request->driverUuid,
                        );
                    }
                }
            }
        }

        if ($isCanceled) {
            // Return money to the user
            yield $requestSaga->compensate();
            $this->taxiRequest = yield $this->taxiOrdering->cancelRequest(
                $this->taxiRequest->uuid,
                $cancelReason,
            );
            return;
        }

        // Driver assigned
        $this->trip = yield $this->taxiOrdering->startTrip($this->taxiRequest->uuid);

        // Start trip workflow
        $this->trip = yield Workflow::newChildWorkflowStub(
            TripWorkflow::class,
            ChildWorkflowOptions::new()
                ->withWorkflowId($this->taxiRequest->uuid . '-trip')
                // Disallow duplicate workflows with the same ID.
                ->withWorkflowIdReusePolicy(IdReusePolicy::AllowDuplicateFailedOnly)
                ->withTaskQueue('taxi-service'),
        )->start($this->trip, $this->taxiRequest->currentLocation);

        // Charge user for the trip
        yield $this->paymentService->commitFunds($transactionUuid);

        // TODO notify user about the trip finish and ask to rate each other
        yield $this->notificationService->tripFinished($this->taxiRequest->uuid);

        // If some of the parties haven't rated each other we can give them some time
        // to rate each other
        while ($this->trip->driverRating === null || $this->trip->userRating === null) {
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
                    $this->trip->driverRating = new Rating($request->rating, $request->comment);
                } elseif ($request instanceof DriverRateRequest) {
                    $this->trip->userRating = new Rating($request->rating, $request->comment);
                }
            }
        }
    }

    #[Workflow\UpdateMethod]
    public function cancelRequest(CancelRequest $request): void
    {
        $this->queue[] = $request;
    }

    #[Workflow\UpdateMethod]
    public function acceptRequest(AcceptRequest $request): void
    {
        $this->queue[] = $request;
    }

    #[Workflow\SignalMethod]
    public function rateUser(UserRateRequest $request)
    {
        $this->queue[] = $request;
    }

    public function rateDriver(DriverRateRequest $request)
    {
        $this->queue[] = $request;
    }

    #[Workflow\UpdateValidatorMethod('acceptRequest')]
    public function validateDriver(AcceptRequest $request)
    {
        // TODO check if we can user activity here
        $isMatch = yield $this->taxiOrdering->validateDriver($this->taxiRequest->uuid, $request);

        return $isMatch->isMatched;
    }
}
