<?php

declare(strict_types=1);

namespace Taxi\Repository;

use Ramsey\Uuid\UuidInterface;
use Taxi\DriverLocation;
use Taxi\Exception\DriverLocationNotFoundException;

interface DriverLocationRepositoryInterface
{
    /**
     * @throws DriverLocationNotFoundException
     */
    public function getByUuid(UuidInterface $driverUuid): DriverLocation;
}
