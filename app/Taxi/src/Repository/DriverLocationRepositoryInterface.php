<?php

declare(strict_types=1);

namespace Taxi\Repository;

use Ramsey\Uuid\UuidInterface;
use Taxi\DriverLocation;
use Taxi\Exception\DriverLocationNotFoundException;

interface DriverLocationRepositoryInterface
{
    /**
     * Get driver location by driver UUID.
     *
     * @throws DriverLocationNotFoundException
     */
    public function getByUuid(UuidInterface $driverUuid): DriverLocation;
}
