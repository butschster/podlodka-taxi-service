<?php

declare(strict_types=1);

namespace Taxi\Repository;

use Cycle\ORM\RepositoryInterface;
use Ramsey\Uuid\UuidInterface;
use Taxi\DriverLocation;
use Taxi\Exception\DriverLocationNotFoundException;

interface DriverLocationRepositoryInterface extends RepositoryInterface
{
    /**
     * Get driver location by driver UUID.
     *
     * @throws DriverLocationNotFoundException
     */
    public function getByUuid(UuidInterface $driverUuid): DriverLocation;
}
