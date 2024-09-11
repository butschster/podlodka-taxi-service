<?php

declare(strict_types=1);

namespace App\Endpoint\Temporal\Activity;

use App\Endpoint\Temporal\Workflow\DTO\AcceptRequest;
use App\Endpoint\Temporal\Workflow\DTO\CreateRequest;
use App\Endpoint\Temporal\Workflow\DTO\DriverStatus;
use Ramsey\Uuid\UuidInterface;
use Spiral\TemporalBridge\Attribute\AssignWorker;
use Taxi\TaxiOrderingService;
use Taxi\TaxiRequest;
use Taxi\Trip;
use Temporal\Activity\ActivityInterface;
use Temporal\Activity\ActivityMethod;
use Temporal\Support\VirtualPromise;

#[AssignWorker('taxi-service')]
#[ActivityInterface(prefix: "taxi-request.")]
final readonly class TaxiRequestActivity
{
    public function __construct(
        private TaxiOrderingService $taxiOrderingService,
    ) {}

    /**
     * @return VirtualPromise<TaxiRequest>
     */
    #[ActivityMethod]
    public function requestTaxi(CreateRequest $request): TaxiRequest
    {
        return $this->taxiOrderingService->createTaxiRequest(
            uuid: $request->requestUuid,
            userUuid: $request->userUuid,
            vehicleClass: $request->vehicleClass,
            currentLocation: $request->currentLocation,
            destinationLocation: $request->destinationLocation,
        );
    }

    /**
     * @return VirtualPromise<TaxiRequest>
     */
    #[ActivityMethod]
    public function assignDriver(UuidInterface $taxiRequestUuid, UuidInterface $driverUuid): TaxiRequest
    {
        return $this->taxiOrderingService->acceptTaxiRequest(
            driverUuid: $driverUuid,
            taxiRequestUuid: $taxiRequestUuid,
        );
    }

    /**
     * @return VirtualPromise<TaxiRequest>
     */
    #[ActivityMethod]
    public function cancelRequest(UuidInterface $taxiRequestUuid, string $reason): TaxiRequest
    {
        return $this->taxiOrderingService->cancelTaxiRequest(
            taxiRequestUuid: $taxiRequestUuid,
            reason: $reason,
        );
    }

    /**
     * @return VirtualPromise<DriverStatus>
     */
    #[ActivityMethod]
    public function validateDriver(UuidInterface $taxiRequestUuid, AcceptRequest $request): DriverStatus
    {
        try {
            $this->taxiOrderingService->validateDriver($taxiRequestUuid, $request->driverUuid);

            return new DriverStatus(isMatched: true);
        } catch (\Throwable $e) {
            return new DriverStatus(isMatched: false, reason: $e->getMessage());
        }
    }

    public function startTrip(UuidInterface $taxiRequestUuid): Trip
    {
        return $this->taxiOrderingService->startTrip($taxiRequestUuid);
    }
}
