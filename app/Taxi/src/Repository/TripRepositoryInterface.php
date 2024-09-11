<?php

declare(strict_types=1);

namespace Taxi\Repository;

use Ramsey\Uuid\UuidInterface;
use Taxi\Exception\TripNotFoundException;
use Taxi\Trip;

interface TripRepositoryInterface
{
    public function findByUuid(UuidInterface $uuid): ?Trip;

    /**
     * @throws TripNotFoundException
     */
    public function getByUuid(UuidInterface $uuid): Trip;


    public function persist(Trip $trip): void;
}
