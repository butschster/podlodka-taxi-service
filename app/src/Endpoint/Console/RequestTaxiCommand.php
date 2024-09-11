<?php

declare(strict_types=1);

namespace App\Endpoint\Console;

use App\Endpoint\Temporal\Workflow\DTO\CreateRequest;
use App\Endpoint\Temporal\Workflow\TaxiRequestWorkflow;
use Ramsey\Uuid\Uuid;
use Spiral\Console\Attribute\AsCommand;
use Spiral\Console\Command;
use Taxi\Location;
use Taxi\VehicleClass;
use Temporal\Client\WorkflowClientInterface;
use Temporal\Client\WorkflowOptions;
use Temporal\Common\IdReusePolicy;

#[AsCommand(name: 'taxi:request')]
final class RequestTaxiCommand extends Command
{
    public function __invoke(WorkflowClientInterface $client): int
    {
        $userUuid = Uuid::uuid7();
        $requestUuid = Uuid::uuid4();

        $client->newWorkflowStub(
            TaxiRequestWorkflow::class,
            WorkflowOptions::new()
                // This is a unique identifier for the workflow execution.
                // It will be used to send signals to the workflow.
                ->withWorkflowId((string) $requestUuid)
                // Disallow duplicate workflows with the same ID.
                ->withWorkflowIdReusePolicy(IdReusePolicy::AllowDuplicateFailedOnly)
                ->withTaskQueue('taxi-service'),
        )->createRequest(
            new CreateRequest(
                requestUuid: $requestUuid,
                userUuid: $userUuid,
                currentLocation: new Location(latitude: 37.7749, longitude: -122.4194), // San Francisco
                destinationLocation: new Location(latitude: 40.7128, longitude: -74.0060), // New York
                vehicleClass: VehicleClass::Business,
            ),
        );

        return self::SUCCESS;
    }
}
