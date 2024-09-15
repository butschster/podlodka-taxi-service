<?php

declare(strict_types=1);

namespace Taxi\Repository;

use Cycle\ORM\RepositoryInterface;
use Ramsey\Uuid\UuidInterface;
use Taxi\Vehicle;

interface VehicleRepositoryInterface extends RepositoryInterface
{
    /**
     * Find vehicle by UUID.
     */
    public function findByUuid(UuidInterface $uuid): ?Vehicle;

    /**
     * Get vehicle by UUID.
     */
    public function getByUuid(UuidInterface $uuid): Vehicle;

    /**
     * Persist vehicle data.
     */
    public function persist(Vehicle $vehicle): void;
}
