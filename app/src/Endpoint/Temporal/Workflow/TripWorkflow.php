<?php

declare(strict_types=1);

namespace App\Endpoint\Temporal\Workflow;

use App\Endpoint\Temporal\Activity\NotificationServiceActivity;
use App\Endpoint\Temporal\Activity\TaxiRequestActivity;
use App\Endpoint\Temporal\Workflow\DTO\FinishRequest;
use App\Endpoint\Temporal\Workflow\DTO\StartTripRequest;
use Carbon\CarbonInterval;
use Taxi\DriverLocation;
use Taxi\Trip;
use Temporal\Activity\ActivityOptions;
use Temporal\Common\RetryOptions;
use Temporal\Internal\Workflow\ActivityProxy;
use Temporal\Support\VirtualPromise;
use Temporal\Workflow;

#[Workflow\WorkflowInterface]
final class TripWorkflow
{
    private ActivityProxy|TaxiRequestActivity $taxiOrdering;
    private ActivityProxy|NotificationServiceActivity $notificationService;

    public function __construct()
    {
        $this->taxiOrdering = Workflow::newActivityStub(
            TaxiRequestActivity::class,
            ActivityOptions::new()
                ->withStartToCloseTimeout(CarbonInterval::minute())
                ->withRetryOptions(
                    RetryOptions::new()
                        ->withMaximumAttempts(2)
                        ->withInitialInterval(CarbonInterval::seconds(5)),
                ),
        );

        $this->notificationService = Workflow::newActivityStub(
            NotificationServiceActivity::class,
            ActivityOptions::new()
                ->withStartToCloseTimeout(CarbonInterval::minutes(5))
                ->withRetryOptions(
                    RetryOptions::new()
                        ->withMaximumAttempts(5)
                        ->withBackoffCoefficient(1.5)
                        ->withInitialInterval(CarbonInterval::seconds(5)),
                ),
        );
    }

    public bool $finished = false;
    private ?DriverLocation $location = null;
    private array $queue = [];

    /**
     * @return VirtualPromise<Trip|null>
     */
    #[Workflow\WorkflowMethod]
    public function start(StartTripRequest $request)
    {
        $trip = yield $this->taxiOrdering->startTrip($request->taxiRequestUuid);

        trip:

        $isArrived = yield Workflow::awaitWithTimeout(
            CarbonInterval::minutes(30),
            fn() => $this->finished,
            fn() => $this->queue !== [],
        );

        if (!$isArrived) {
            yield $this->notificationService->driverNotResponding($request->taxiRequestUuid);
            return null;
        }

        while ($this->queue !== []) {
            $dto = \array_shift($this->queue);

            $this->location = $dto;
//            yield $this->taxiOrdering->updateLocation($dto);
        }

        if (!$this->finished) {
            goto trip;
        }

        $currentTime = yield Workflow::now();

        yield $this->notificationService->tripFinished($request->taxiRequestUuid);

        return yield $this->taxiOrdering->finishTrip(
            new FinishRequest(
                tripUuid: $trip->uuid,
                time: $currentTime,
                location: $this->location,
            ),
        );
    }


    #[Workflow\SignalMethod]
    public function finish(): void
    {
        $this->finished = true;
    }

    #[Workflow\SignalMethod]
    public function updateLocation(DriverLocation $location): void
    {
        $this->queue[] = $location;
    }

}
