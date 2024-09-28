<?php

declare(strict_types=1);

namespace App\Endpoint\Console;

use App\Endpoint\Temporal\Workflow\DTO\AcceptRequest;
use App\Endpoint\Temporal\Workflow\TaxiRequestWorkflow;
use Ramsey\Uuid\Uuid;
use Spiral\Console\Attribute\Argument;
use Spiral\Console\Attribute\AsCommand;
use Spiral\Console\Command;
use Taxi\DriverLocation;
use Taxi\Location;
use Temporal\Client\WorkflowClientInterface;

#[AsCommand(name: 'taxi:request:accept')]
final class AcceptRequestCommand extends Command
{
    #[Argument(name: 'request_id', description: 'The ID of the request to accept')]
    public string $requestUuid;

    public function __invoke(WorkflowClientInterface $client): int
    {
        $requestUuid = Uuid::fromString($this->requestUuid);

        $wf = $client->newRunningWorkflowStub(
            class: TaxiRequestWorkflow::class,
            workflowID: $requestUuid->toString(),
        );

        $driverUuid = Uuid::fromString('0191f9a2-b1b3-7284-b8b7-ada86e2c3029');

        $wf->acceptRequest(
            new AcceptRequest(
                driverUuid: $driverUuid,
                currentLocation: new DriverLocation(
                    location: new Location(
                        latitude: 37.7749,
                        longitude: -122.4194,
                    ),
                    timestamp: new \DateTimeImmutable(),
                ),
            ),
        );


        return self::SUCCESS;
    }
}
