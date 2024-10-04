<?php

declare(strict_types=1);

namespace App\Endpoint\Console;

use App\Endpoint\Temporal\Workflow\DTO\CreateRequest;
use App\Endpoint\Temporal\Workflow\TaxiRequestWorkflow;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Spiral\Console\Attribute\AsCommand;
use Spiral\Console\Command;
use Taxi\Location;
use Taxi\VehicleClass;
use Temporal\Client\WorkflowClientInterface;
use Temporal\Client\WorkflowOptions;

#[AsCommand(name: 'taxi:request')]
final class RequestTaxiCommand extends Command
{
    public function __invoke(WorkflowClientInterface $client)
    {
        // 1. Генерируем UUID для запроса, который мы можем отдать клиенту
        $requestUuid = Uuid::uuid4();
        $this->printInfo($requestUuid);

        $userUuid = Uuid::fromString('0191f9a0-87e0-72c2-aed9-ce3905267238');

        $wf = $client->newWorkflowStub(
            TaxiRequestWorkflow::class,
            WorkflowOptions::new()
                ->withWorkflowId((string) $requestUuid),
        );

        $client->start(
            $wf,
            new CreateRequest(
                requestUuid: $requestUuid,
                userUuid: $userUuid,
                currentLocation: new Location(latitude: 37.7749, longitude: -122.4194), // San Francisco
                destinationLocation: new Location(latitude: 40.7128, longitude: -74.0060), // New York
                vehicleClass: VehicleClass::Business,
            ),
        );
    }

    private function printInfo(UuidInterface $requestUuid): void
    {
        $this->info('Requesting a taxi...');
        $this->info('Request UUID: ' . $requestUuid);

        $this->warning('1. Принять запрос водителем:');
        $this->info('php app.php t:r:accept ' . $requestUuid);

        $this->warning('2. Отменить заказ клиентом:');
        $this->info('php app.php t:r:cancel ' . $requestUuid);

        $this->warning('3. Сообщить о том, что водитель прибыл:');
        $this->info('php app.php t:r:driver-arrived ' . $requestUuid);

        $this->warning('4. Обновить текущее местоположение:');
        $this->info('php app.php t:r:update-location ' . $requestUuid);

        $this->warning('5. Завершить поездку:');
        $this->info('php app.php t:r:finish ' . $requestUuid);
    }
}
