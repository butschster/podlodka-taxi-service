<?php

declare(strict_types=1);

namespace Taxi\Repository;

use Cycle\ORM\RepositoryInterface;
use Ramsey\Uuid\UuidInterface;
use Taxi\Driver;
use Taxi\Exception\DriverNotFoundException;
use Taxi\Location;

interface DriverRepositoryInterface extends RepositoryInterface
{
    /**
     * Find driver by UUID.
     */
    public function findByUuid(UuidInterface $uuid): ?Driver;

    /**
     * Get driver by UUID.
     *
     * @throws DriverNotFoundException
     */
    public function getByUuid(UuidInterface $uuid): Driver;

    /**
     * Persist driver data.
     */
    public function persist(Driver $driver): void;

    /**
     * Find available drivers nearby the location.
     *
     * @param Location $location Location to search nearby
     * @param float $radiusKm Radius in kilometers
     *
     * @return Driver[]
     */
    public function findAvailableDriversNearby(Location $location, float $radiusKm): iterable;
}
