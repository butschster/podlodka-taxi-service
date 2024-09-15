<?php

declare(strict_types=1);

namespace Taxi\Repository;

use Cycle\ORM\RepositoryInterface;
use Ramsey\Uuid\UuidInterface;
use Taxi\Exception\TripNotFoundException;
use Taxi\Trip;

interface TripRepositoryInterface extends RepositoryInterface
{
    /**
     * Find trip by UUID.
     */
    public function findByUuid(UuidInterface $uuid): ?Trip;

    /**
     * Get trip by UUID.
     *
     * @throws TripNotFoundException
     */
    public function getByUuid(UuidInterface $uuid): Trip;

    /**
     * Persist trip data.
     */
    public function persist(Trip $trip): void;
}
