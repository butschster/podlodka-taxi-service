<?php

declare(strict_types=1);

namespace Taxi;

final readonly class PriceCalculatorService
{
    public function __construct(
        private DistanceCalculatorService $distanceCalculatorService,
    ) {}

    /**
     * @param int $duration Duration trip time in minutes
     */
    private function calculatePrice(
        Location $currentLocation,
        Location $destinationLocation,
        int $duration,
        VehicleClass $vehicleClass,
    ): float {
        $traffic = $this->getTraffic($currentLocation, $destinationLocation);
        $weather = $this->getWeather($currentLocation, $destinationLocation);

        $vehicleClassCoefficient = match ($vehicleClass) {
            VehicleClass::Comfort => 1.2,
            VehicleClass::Business => 1.5,
            default => 1,
        };

        // Ou driver charges $1 per minute
        // The price can be calculated based on the traffic, weather, and other factors
        return \round($duration * $traffic * $weather * $vehicleClassCoefficient, 2);
    }

    public function calculateEstimatedPrice(
        Location $currentLocation,
        Location $destinationLocation,
        VehicleClass $vehicleClass,
    ): float {
        // Estimated price is calculated based on the distance between the current location and the destination location
        // Also estimated price can be calculated based on the traffic, weather, and other factors
        $distance = $this->distanceCalculatorService->calculateDistance($currentLocation, $destinationLocation);

        // Our driver drives 10 km per hour
        $estimatedDuration = $distance / 10 * 60;

        return $this->calculatePrice($currentLocation, $destinationLocation, (int) $estimatedDuration, $vehicleClass);
    }

    public function calculateFinalPrice(
        Location $currentLocation,
        Location $destinationLocation,
        \DateTimeImmutable $startTime,
        \DateTimeImmutable $endTime,
        VehicleClass $vehicleClass,
    ): float {
        // Duration of the trip in minutes
        $duration = $startTime->diff($endTime)->i;

        return $this->calculatePrice($currentLocation, $destinationLocation, $duration, $vehicleClass);
    }

    private function calculateDistance(Location $currentLocation, Location $destinationLocation): int
    {
        return \rand(1, 100);
    }

    private function getTraffic(
        Location $currentLocation,
        Location $destinationLocation,
    ): float {
        // This is a dummy implementation
        // In a real-world application, you would use a library like Google Maps API to get the traffic information
        return \rand(1, 10) / 10;
    }

    private function getWeather(
        Location $currentLocation,
        Location $destinationLocation,
    ): float {
        // This is a dummy implementation
        // In a real-world application, you would use a library like OpenWeatherMap API to get the weather information
        return \rand(1, 10) / 10;
    }
}
