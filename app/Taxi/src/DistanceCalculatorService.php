<?php

declare(strict_types=1);

namespace Taxi;

final class DistanceCalculatorService
{
    /**
     * Calculate the distance between two locations
     *
     * @return float Distance in kilometers
     */
    public function calculateDistance(Location $locationA, Location $locationB): float
    {
        // This is a dummy implementation
        // In a real-world application, you would use a library like GeoIP or Google Maps API to calculate the distance
        // between two locations
        return (float) \rand(1, 100);
    }
}
