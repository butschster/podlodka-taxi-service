<?php

declare(strict_types=1);

namespace App\Infrastructure\CycleORM\Repository;

use Ramsey\Uuid\UuidInterface;
use Taxi\DriverLocation;
use Taxi\Location;
use Taxi\Repository\DriverLocationRepositoryInterface;

final class DriverLocationRepository implements DriverLocationRepositoryInterface
{
    public function getByUuid(UuidInterface $driverUuid): DriverLocation
    {
        return new DriverLocation(
            location: new Location(
                latitude: 40.7128,
                longitude: -74.0060,
            ),
            timestamp: new \DateTimeImmutable(),
        );
    }
}
