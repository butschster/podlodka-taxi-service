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

#[AsCommand(name: 'taxi:request:finish-trip')]
final class FinishTripCommand extends Command
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

        $wf->finish();

        return self::SUCCESS;
    }
}
