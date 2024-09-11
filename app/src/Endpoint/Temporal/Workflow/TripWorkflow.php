<?php

declare(strict_types=1);

namespace App\Endpoint\Temporal\Workflow;

use App\Endpoint\Temporal\Workflow\DTO\DriverRateRequest;
use App\Endpoint\Temporal\Workflow\DTO\UserRateRequest;
use Carbon\CarbonInterval;
use Spiral\TemporalBridge\Attribute\AssignWorker;
use Taxi\DriverLocation;
use Taxi\Rating;
use Taxi\Trip;
use Temporal\Support\VirtualPromise;
use Temporal\Workflow;
use Temporal\Workflow\SignalMethod;
use Temporal\Workflow\WorkflowInterface;
use Temporal\Workflow\WorkflowMethod;

#[AssignWorker('taxi-service')]
#[WorkflowInterface]
final class TripWorkflow
{
    private ?DriverLocation $driverLocation = null;
    private bool $finished = false;
    private array $queue = [];

    /**
     * @return VirtualPromise<Trip>
     */
    #[WorkflowMethod]
    public function start(Trip $trip, DriverLocation $location)
    {
        $this->driverLocation = $location;

        $triesToAskDriver = 0;

        while (true) {
            $isEvent = yield Workflow::awaitWithTimeout(
            // TODO: use estimated time to destination
                CarbonInterval::hours(5),
                fn() => $this->finished,
                fn() => $this->queue !== [],
            );

            if (!$isEvent) {
                // TODO: implement this
                // check if the trip is finished
                // ask driver if everything is ok

                if ($triesToAskDriver >= 3) {
                    // Driver is not responding
                    // Something bad happened
                    // Ask manager to call driver
                    // Call 911 if no response
                    // Handle the situation manually
                    break;
                }

                $triesToAskDriver++;
                continue;
            }

            if ($this->finished) {
                // TODO: implement this
                // Update trip status in the database
                // Send notification to the user
                // Ask user and driver to rate each other
                break;
            }

            while ($this->queue !== []) {
                $request = \array_shift($this->queue);

                if ($request instanceof UserRateRequest) {
                    $trip->userRating = new Rating(
                        $request->rating,
                        $request->comment,
                    );
                } elseif ($request instanceof DriverRateRequest) {
                    $trip->driverRating = new Rating(
                        $request->rating,
                        $request->comment,
                    );
                }
            }
        }

        return $trip;
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

    #[SignalMethod]
    public function finish(): void
    {
        $this->finished = true;
    }

    #[SignalMethod]
    public function updateLocation(DriverLocation $location): void
    {
        $this->driverLocation = $location;
    }

    public function currentLocation(): string
    {
        return \json_encode($this->driverLocation->location);
    }
}
