<?php

declare(strict_types=1);

namespace App\Endpoint\Console;

use App\Endpoint\Temporal\Workflow\TaxiRequestWorkflow;
use Ramsey\Uuid\Uuid;
use Spiral\Console\Attribute\Argument;
use Spiral\Console\Attribute\AsCommand;
use Spiral\Console\Command;
use Temporal\Client\WorkflowClientInterface;

#[AsCommand(name: 'taxi:request:driver-arrived')]
final class DriverArrivedRequestCommand extends Command
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

        $wf->driverArrived('Жду вас у подъезда!');

        return self::SUCCESS;
    }
}
