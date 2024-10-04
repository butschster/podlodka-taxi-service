<?php

declare(strict_types=1);

namespace App\Endpoint\Temporal\Activity;

use App\Endpoint\Temporal\Workflow\DTO\AcceptRequest;
use App\Endpoint\Temporal\Workflow\DTO\CreateRequest;
use App\Endpoint\Temporal\Workflow\DTO\DriverStatus;
use App\Endpoint\Temporal\Workflow\DTO\FinishRequest;
use Ramsey\Uuid\UuidInterface;
use Taxi\TaxiOrderingService;
use Taxi\TaxiRequest;
use Taxi\Trip;
use Temporal\Activity\ActivityInterface;
use Temporal\Activity\ActivityMethod;
use Temporal\Support\VirtualPromise;

#[ActivityInterface]
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
        dump($request);

        return $this->taxiOrderingService->createTaxiRequest(
            uuid: $request->requestUuid,
            userUuid: $request->userUuid,
            vehicleClass: $request->vehicleClass,
            currentLocation: $request->currentLocation,
            destinationLocation: $request->destinationLocation,
        );
    }

    /**
     * @return VirtualPromise<DriverStatus>
     */
    #[ActivityMethod]
    public function validateDriver(UuidInterface $taxiRequestUuid, AcceptRequest $request): DriverStatus
    {
        dump($taxiRequestUuid, $request);

        try {
            $this->taxiOrderingService->validateDriver($taxiRequestUuid, $request->driverUuid);

            return new DriverStatus(isMatched: true);
        } catch (\Throwable $e) {
            return new DriverStatus(isMatched: false, reason: $e->getMessage());
        }
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
     * @return VirtualPromise<Trip>
     */
    #[ActivityMethod]
    public function startTrip(UuidInterface $taxiRequestUuid): Trip
    {
        return $this->taxiOrderingService->startTrip($taxiRequestUuid);
    }

    /**
     * @return VirtualPromise<Trip>
     */
    #[ActivityMethod]
    public function finishTrip(FinishRequest $request): Trip
    {
        return $this->taxiOrderingService->endTrip(
            $request->tripUuid,
            $request->time,
            $request->location,
        );
    }
}
