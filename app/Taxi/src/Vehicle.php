<?php

declare(strict_types=1);

namespace Taxi;

use App\Infrastructure\CycleORM\Table\VehicleTable;
use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Ramsey\Uuid\UuidInterface;
use Taxi\Repository\VehicleRepositoryInterface;

#[Entity(
    role: Vehicle::ROLE,
    repository: VehicleRepositoryInterface::class,
    table: VehicleTable::TABLE_NAME
)]
class Vehicle
{
    public const ROLE = 'vehicle';

    public const F_UUID = 'uuid';
    public const F_DRIVER_UUID = 'driverUuid';
    public const F_LICENSE_PLATE = 'licensePlate';
    public const F_MODEL = 'model';
    public const F_VEHICLE_CLASS = 'vehicleClass';
    public const F_CREATED_AT = 'createdAt';

    #[Column(type: 'datetime', name: VehicleTable::CREATED_AT)]
    public \DateTimeInterface $createdAt;

    public function __construct(
        #[Column(type: 'uuid', name: VehicleTable::UUID, primary: true, typecast: 'uuid')]
        public UuidInterface $uuid,
        #[Column(type: 'uuid', name: VehicleTable::DRIVER_UUID, typecast: 'uuid')]
        public UuidInterface $driverUuid,
        #[Column(type: 'string', name: VehicleTable::LICENSE_PLATE)]
        public string $licensePlate,
        #[Column(type: 'string', name: VehicleTable::MODEL)]
        public string $model,
        #[Column(type: 'string', name: VehicleTable::VEHICLE_CLASS, typecast: VehicleClass::class)]
        public VehicleClass $class,
    ) {
        $this->createdAt = new \DateTimeImmutable();
    }
}
