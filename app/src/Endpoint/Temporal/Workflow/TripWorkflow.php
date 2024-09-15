<?php

declare(strict_types=1);

namespace App\Endpoint\Temporal\Workflow;

use App\Endpoint\Temporal\Activity\NotificationServiceActivity;
use App\Endpoint\Temporal\Activity\TaxiRequestActivity;
use App\Endpoint\Temporal\Workflow\DTO\DriverRateRequest;
use App\Endpoint\Temporal\Workflow\DTO\StartTripRequest;
use App\Endpoint\Temporal\Workflow\DTO\UserRateRequest;
use Carbon\CarbonInterval;
use Spiral\TemporalBridge\Attribute\AssignWorker;
use Taxi\DriverLocation;
use Taxi\Trip;
use Temporal\Activity\ActivityOptions;
use Temporal\Internal\Workflow\ActivityProxy;
use Temporal\Support\VirtualPromise;
use Temporal\Workflow;

#[AssignWorker('taxi-service')]
#[Workflow\WorkflowInterface]
final class TripWorkflow
{
    private ActivityProxy|TaxiRequestActivity $taxiOrdering;
    private ActivityProxy|NotificationServiceActivity $notificationService;

    private ?DriverLocation $driverLocation = null;
    private bool $finished = false;
    private array $queue = [];

    public function __construct()
    {
        $this->taxiOrdering = Workflow::newActivityStub(
            TaxiRequestActivity::class,
            ActivityOptions::new()
                ->withStartToCloseTimeout(CarbonInterval::minute())
                ->withTaskQueue('taxi-service'),
        );

        $this->notificationService = Workflow::newActivityStub(
            NotificationServiceActivity::class,
            ActivityOptions::new()
                ->withStartToCloseTimeout(CarbonInterval::minutes(5))
                ->withTaskQueue('payment-service'),
        );
    }

    /**
     * @return VirtualPromise<Trip>
     */
    #[Workflow\WorkflowMethod]
    public function start(StartTripRequest $request)
    {
        // If driver was assigned, we can start the trip
        $trip = yield $this->taxiOrdering->startTrip($request->taxiRequestUuid);

        $triesToAskDriver = 0;

        while (true) {
            $isEvent = yield Workflow::awaitWithTimeout(
                // TODO: use estimated time to destination
                CarbonInterval::minutes(60),
                fn() => $this->finished,
                fn() => $this->queue !== [],
            );

            if (!$isEvent) {
                // TODO: implement this
                // check if the trip is finished
                // ask driver if everything is ok
                yield $this->notificationService->askDriverIfEverythingIsOk($request->taxiRequestUuid);

                if ($triesToAskDriver >= 3) {
                    // Driver is not responding
                    // Something bad happened
                    // Ask manager to call driver
                    // Call 911 if no response
                    // Handle the situation manually

                    yield $this->notificationService->driverNotResponding($request->taxiRequestUuid);

                    break;
                }

                $triesToAskDriver++;
                continue;
            }

            if ($this->finished) {
                // Update trip status in the database
                // Send notification to the user
                $currentTime = yield Workflow::sideEffect(fn() => new \DateTimeImmutable());

                yield $this->notificationService->tripFinished($request->taxiRequestUuid);

                $trip = yield $this->taxiOrdering->finishTrip(
                    $request->taxiRequestUuid,
                    $currentTime,
                    $this->driverLocation,
                );
                break;
            }

            while ($this->queue !== []) {
                $request = \array_shift($this->queue);

                if ($request instanceof UserRateRequest) {
                    $trip = yield $this->taxiOrdering->rateUser($trip->uuid, $request);
                } elseif ($request instanceof DriverRateRequest) {
                    $trip = yield $this->taxiOrdering->rateDriver($trip->uuid, $request);
                } elseif ($request instanceof DriverLocation) {
                    $this->driverLocation = $request;
                }
            }
        }

        return $trip;
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

    #[Workflow\QueryMethod]
    public function currentLocation(): string
    {
        return $this->driverLocation
            ? \json_encode($this->driverLocation->location)
            : 'unknown';
    }
}
