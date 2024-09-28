<?php

declare(strict_types=1);

namespace Taxi;

use Ramsey\Uuid\Uuid;
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

    /**
     * Creates a new taxi request for a user.
     */
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
            userUuid: $userUuid,
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

    /**
     * Accepts a taxi request by a driver.
     * The driver must be available and close to the user.
     */
    public function acceptTaxiRequest(UuidInterface $driverUuid, UuidInterface $taxiRequestUuid): TaxiRequest
    {
        $driver = $this->drivers->getByUuid($driverUuid);

        $this->validateDriver($taxiRequestUuid, $driverUuid);

        $taxiRequest = $this->taxiRequests->getByUuid($taxiRequestUuid);

        $taxiRequest->accept($driverUuid);
        $this->taxiRequests->persist($taxiRequest);

        $driver->isAvailable = false;
        $this->drivers->persist($driver);

        return $taxiRequest;
    }

    /**
     * Starts the trip for a taxi request. Driver must have accepted the request before starting the trip.
     */
    public function startTrip(UuidInterface $requestUuid): Trip
    {
        $taxiRequest = $this->taxiRequests->getByUuid($requestUuid);

        $trip = $taxiRequest->startTrip();
        $this->trips->persist($trip);
        $this->taxiRequests->persist($taxiRequest);

        return $trip;
    }

    /**
     * Ends the trip for a taxi request.
     */
    public function endTrip(UuidInterface $tripUuid, \DateTimeImmutable $endTime, DriverLocation|null $location): Trip
    {
        $trip = $this->trips->getByUuid($tripUuid);
        $request = $this->taxiRequests->getByUuid($trip->taxiRequestUuid);

        $finalPrice = $this->priceCalculator->calculateFinalPrice(
            currentLocation: $request->currentLocation,
            destinationLocation: $location->location ?? $request->destinationLocation,
            startTime: $trip->startTime,
            endTime: $endTime,
            vehicleClass: $request->vehicleClass,
        );

        $trip->finish($endTime, $finalPrice);
        $request->complete($endTime);

        $this->taxiRequests->persist($request);
        $this->trips->persist($trip);

        $driver = $this->drivers->getByUuid($request->driverUuid);
        $driver->isAvailable = true;
        $this->drivers->persist($driver);

        return $trip;
    }

    /**
     * When a driver accepts a taxi request, we need to validate if the driver is available, close to the user,
     * and if the driver's vehicle class matches the request.
     *
     * @throws DriverUnavailableException
     * @throws InvalidRequestStatusException
     */
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
        if ($distance > 50) {
            throw new InvalidRequestStatusException('Driver is too far from the user');
        }
    }

    /**
     * Cancels a taxi request by the user.
     */
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

        if ($taxiRequest->isAccepted()) {
            $driver = $this->drivers->getByUuid($taxiRequest->driverUuid);
            $driver->isAvailable = true;
            $this->drivers->persist($driver);
        }

        return $taxiRequest;
    }

    /**
     * Checks if the user can make a new request.
     */
    private function canUserMakeRequest(User $user): bool
    {
        // Here we can implement some logic to check if the user can make a new request
        // For example, we can check the number of cancelled requests in the last 24 hours
        // Or we can check the number of requests made in the last hour

        return true;
    }

    /**
     * Rates the user by the driver during a trip or after it ends.
     */
    public function rateUser(UuidInterface $tripUuid, int $rating, ?string $comment): Trip
    {
        $trip = $this->trips->getByUuid($tripUuid);
        $request = $this->taxiRequests->getByUuid($trip->taxiRequestUuid);
        $user = $this->users->getByUuid($request->userUuid);

        $user->addRating(
            $rating = new Rating(
                uuid: Uuid::uuid7(),
                tripUuid: $tripUuid,
                recipientUuid: $user->uuid,
                rating: $rating,
                comment: $comment,
            ),
        );

        $this->users->persist($user);

        $trip->userRatingUuid = $rating->uuid;
        $this->trips->persist($trip);

        return $trip;
    }

    /**
     * Rates the driver by the user after the trip ends or during it.
     */
    public function rateDriver(UuidInterface $tripUuid, int $rating, ?string $comment): Trip
    {
        $trip = $this->trips->getByUuid($tripUuid);
        $request = $this->taxiRequests->getByUuid($trip->taxiRequestUuid);
        $driver = $this->drivers->getByUuid($request->driverUuid);

        $driver->addRating(
            $rating = new Rating(
                uuid: Uuid::uuid7(),
                tripUuid: $tripUuid,
                recipientUuid: $driver->uuid,
                rating: $rating,
                comment: $comment,
            ),
        );

        $this->drivers->persist($driver);

        $trip->driverRatingUuid = $rating->uuid;
        $this->trips->persist($trip);

        return $trip;
    }
}
