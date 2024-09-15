<?php

declare(strict_types=1);

namespace App\Infrastructure\CycleORM\Repository;

use Cycle\ORM\EntityManagerInterface;
use Cycle\ORM\Select;
use Cycle\ORM\Select\Repository;
use Ramsey\Uuid\UuidInterface;
use Taxi\Driver;
use Taxi\Exception\DriverNotFoundException;
use Taxi\Location;
use Taxi\Repository\DriverRepositoryInterface;

/**
 * @extends Repository<Driver>
 */
final class DriverRepository extends Repository implements DriverRepositoryInterface
{
    public function __construct(
        Select $select,
        private readonly EntityManagerInterface $em,
    ) {
        parent::__construct($select);
    }

    public function findByUuid(UuidInterface $uuid): ?Driver
    {
        return $this->select()->where(Driver::F_UUID, $uuid)->fetchOne();
    }

    public function getByUuid(UuidInterface $uuid): Driver
    {
        $driver = $this->findByUuid($uuid);
        if ($driver === null) {
            throw new DriverNotFoundException(\sprintf('Driver with UUID "%s" not found', $uuid->toString()));
        }

        return $driver;
    }

    public function persist(Driver $driver): void
    {
        $this->em->persist($driver)->run();
    }

    public function findAvailableDriversNearby(Location $location, float $radiusKm): iterable
    {
        // This is a placeholder implementation
        return $this->select()->fetchAll();
    }
}
