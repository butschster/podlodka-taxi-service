<?php

declare(strict_types=1);

namespace Taxi\Repository;

use Ramsey\Uuid\UuidInterface;
use Taxi\Driver;
use Taxi\Exception\DriverNotFoundException;

interface DriverRepositoryInterface
{
    public function findByUuid(UuidInterface $uuid): ?Driver;

    /**
     * @throws DriverNotFoundException
     */
    public function getByUuid(UuidInterface $uuid): Driver;


    public function persist(Driver $taxiRequest): void;
}
