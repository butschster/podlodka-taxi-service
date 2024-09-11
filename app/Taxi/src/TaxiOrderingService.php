<?php

declare(strict_types=1);

namespace Taxi;

use Ramsey\Uuid\UuidInterface;
use Taxi\Exception\DriverUnavailableException;
use Taxi\Exception\InvalidRequestStatusException;
use Taxi\Exception\RateLimitExceededException;
use Taxi\Repository\DriverLocationRepositoryInterface;
use Taxi\Repository\DriverRepositoryInterface;
use Taxi\Repository\TaxiRequestRepositoryInterface;
use Taxi\Repository\TripRepositoryInterface;
use Taxi\Repository\UserRepositoryInterface;

final readonly class TaxiOrderingService
{
    public function __construct(
        private PriceCalculatorService $priceCalculator,
        private UserRepositoryInterface $users,
        private DriverRepositoryInterface $drivers,
        private TaxiRequestRepositoryInterface $taxiRequests,
        private TripRepositoryInterface $trips,
        private DriverLocationRepositoryInterface $driverLocations,
        private DistanceCalculatorService $distanceCalculator,
    ) {}

    public function createTaxiRequest(
        UuidInterface $uuid,
        UuidInterface $userUuid,
        VehicleClass $vehicleClass,
        Location $currentLocation,
        Location $destinationLocation,
    ): TaxiRequest {
        $user = $this->users->getByUuid($userUuid);

        if (!$this->canUserMakeRequest($user)) {
            throw new RateLimitExceededException(\sprintf('User %s has exceeded the rate limit', $user->uuid));
        }

        $taxiRequest = new TaxiRequest(
            uuid: $uuid,
            user: $user,
            currentLocation: $currentLocation,
            destinationLocation: $destinationLocation,
            vehicleClass: $vehicleClass,
            estimatedPrice: $this->priceCalculator->calculateEstimatedPrice(
                currentLocation: $currentLocation,
                destinationLocation: $destinationLocation,
                vehicleClass: $vehicleClass,
            ),
        );

        $this->taxiRequests->persist($taxiRequest);

        return $taxiRequest;
    }

    public function startTrip(UuidInterface $requestUuid): Trip
    {
        $taxiRequest = $this->taxiRequests->getByUuid($requestUuid);

        if (!$taxiRequest->isAccepted()) {
            throw new InvalidRequestStatusException(\sprintf('Cannot start trip for request %s', $requestUuid));
        }

        $trip = $taxiRequest->startTrip();

        $this->taxiRequests->persist($taxiRequest);
        $this->trips->persist($trip);

        return $trip;
    }

    public function endTrip(UuidInterface $tripUuid, \DateTimeImmutable $endTime): Trip
    {
        $trip = $this->trips->getByUuid($tripUuid);
        $request = $this->taxiRequests->getByUuid($trip->request->uuid);

        $finalPrice = $this->priceCalculator->calculateFinalPrice(
            currentLocation: $request->currentLocation,
            destinationLocation: $request->destinationLocation,
            startTime: $trip->startTime,
            endTime: $endTime,
            vehicleClass: $request->vehicleClass,
        );

        $trip->finish($endTime, $finalPrice);
        $request->complete($endTime);

        $this->taxiRequests->persist($request);
        $this->trips->persist($trip);

        return $trip;
    }

    public function validateDriver(UuidInterface $taxiRequestUuid, UuidInterface $driverUuid): void
    {
        $driver = $this->drivers->getByUuid($driverUuid);

        if (!$driver->isAvailable) {
            throw new DriverUnavailableException('Driver is not available');
        }

        $taxiRequest = $this->taxiRequests->getByUuid($taxiRequestUuid);

        if (!$taxiRequest->isPending()) {
            throw new InvalidRequestStatusException('Taxi request is not in pending status');
        }

        if ($taxiRequest->vehicleClass !== $driver->vehicle->class) {
            throw new InvalidRequestStatusException('Driver vehicle class does not match the request');
        }

        $driverLocation = $this->driverLocations->getByUuid($driverUuid);

        // Validate driver location
        $distance = $this->distanceCalculator->calculateDistance(
            $driverLocation->location,
            $taxiRequest->currentLocation,
        );

        // It can't be more than 2 km away
        if ($distance > 2) {
            throw new InvalidRequestStatusException('Driver is too far from the user');
        }
    }

    public function acceptTaxiRequest(UuidInterface $driverUuid, UuidInterface $taxiRequestUuid): TaxiRequest
    {
        $driver = $this->drivers->getByUuid($driverUuid);

        $this->validateDriver($taxiRequestUuid, $driverUuid);

        $taxiRequest = $this->taxiRequests->getByUuid($taxiRequestUuid);

        $taxiRequest->accept($driver);
        $this->taxiRequests->persist($taxiRequest);

        $driver->isAvailable = false;
        $this->drivers->persist($driver);

        return $taxiRequest;
    }

    public function cancelTaxiRequest(
        UuidInterface $taxiRequestUuid,
        string $reason = 'I don\'t need a taxi anymore',
    ): TaxiRequest {
        $taxiRequest = $this->taxiRequests->getByUuid($taxiRequestUuid);

        if (!$taxiRequest->isPending()) {
            throw new InvalidRequestStatusException('Taxi request cannot be cancelled');
        }

        $taxiRequest->cancel($reason);
        $this->taxiRequests->persist($taxiRequest);

        return $taxiRequest;
    }

    private function canUserMakeRequest(User $user): bool
    {
        // Rate limit logic

        return true;
    }
}
