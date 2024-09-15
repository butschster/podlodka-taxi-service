<?php

declare(strict_types=1);

namespace App\Infrastructure\CycleORM\Repository;

use Cycle\ORM\EntityManagerInterface;
use Cycle\ORM\Select;
use Cycle\ORM\Select\Repository;
use Ramsey\Uuid\UuidInterface;
use Taxi\Exception\VehicleNotFoundException;
use Taxi\Repository\VehicleRepositoryInterface;
use Taxi\Vehicle;

/**
 * @extends Repository<Vehicle>
 */
final class VehicleRepository extends Repository implements VehicleRepositoryInterface
{
    public function __construct(
        Select $select,
        private readonly EntityManagerInterface $em,
    ) {
        parent::__construct($select);
    }

    public function findByUuid(UuidInterface $uuid): ?Vehicle
    {
        return $this->select()->where(Vehicle::F_UUID, $uuid)->fetchOne();
    }

    public function getByUuid(UuidInterface $uuid): Vehicle
    {
        $vehicle = $this->findByUuid($uuid);
        if ($vehicle === null) {
            throw new VehicleNotFoundException(\sprintf('Vehicle with UUID "%s" not found', $uuid->toString()));
        }

        return $vehicle;
    }

    public function persist(Vehicle $vehicle): void
    {
        $this->em->persist($vehicle)->run();
    }
}
