<?php

declare(strict_types=1);

namespace App\Infrastructure\CycleORM\Repository;

use Cycle\ORM\EntityManagerInterface;
use Cycle\ORM\Select;
use Cycle\ORM\Select\Repository;
use Ramsey\Uuid\UuidInterface;
use Taxi\Exception\TripNotFoundException;
use Taxi\Repository\TripRepositoryInterface;
use Taxi\Trip;

/**
 * @extends Repository<Trip>
 */
final class TripRepository extends Repository implements TripRepositoryInterface
{
    public function __construct(
        Select $select,
        private readonly EntityManagerInterface $em,
    ) {
        parent::__construct($select);
    }

    public function findByUuid(UuidInterface $uuid): ?Trip
    {
        return $this->select()->where(Trip::F_UUID, $uuid)->fetchOne();
    }

    public function getByUuid(UuidInterface $uuid): Trip
    {
        $trip = $this->findByUuid($uuid);
        if ($trip === null) {
            throw new TripNotFoundException(\sprintf('Trip with UUID "%s" not found', $uuid->toString()));
        }

        return $trip;
    }

    public function persist(Trip $trip): void
    {
        $this->em->persist($trip)->run();
    }
}
