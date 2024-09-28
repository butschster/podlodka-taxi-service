<?php

declare(strict_types=1);

namespace App\Endpoint\Console;

use App\Endpoint\Temporal\Workflow\TripWorkflow;
use Ramsey\Uuid\Uuid;
use Spiral\Console\Attribute\Argument;
use Spiral\Console\Attribute\AsCommand;
use Spiral\Console\Command;
use Taxi\DriverLocation;
use Taxi\Location;
use Temporal\Client\WorkflowClientInterface;

#[AsCommand(name: 'taxi:request:update-location')]
final class UpdateLocationCommand extends Command
{
    #[Argument(name: 'request_id', description: 'The ID of the request to accept')]
    public string $requestUuid;

    public function __invoke(WorkflowClientInterface $client): int
    {
        $requestUuid = Uuid::fromString($this->requestUuid);

        $wf = $client->newRunningWorkflowStub(
            class: TripWorkflow::class,
            workflowID: $requestUuid->toString() . '-trip',
        );

        $wf->updateLocation(
            new DriverLocation(
                location: new Location(
                    latitude: rand(37, 38) + rand() / getrandmax(),
                    longitude: rand(55, 56) + rand() / getrandmax(),
                ),
                timestamp: new \DateTimeImmutable(),
            ),
        );


        return self::SUCCESS;
    }
}
