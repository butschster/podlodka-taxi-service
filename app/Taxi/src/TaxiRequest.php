<?php

declare(strict_types=1);

namespace Taxi;

use App\Infrastructure\CycleORM\Table\TaxiRequestTable;
use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Relation\HasOne;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Taxi\Exception\DriverUnavailableException;
use Taxi\Exception\InvalidRequestStatusException;
use Taxi\Repository\TaxiRequestRepositoryInterface;

#[Entity(
    role: TaxiRequest::ROLE,
    repository: TaxiRequestRepositoryInterface::class,
    table: TaxiRequestTable::TABLE_NAME
)]
class TaxiRequest
{
    public const ROLE = 'taxi_request';

    public const F_UUID = 'uuid';
    public const F_TRIP_UUID = 'tripUuid';
    public const F_USER_UUID = 'userUuid';
    public const F_DRIVER_UUID = 'driverUuid';
    public const F_CURRENT_LOCATION = 'currentLocation';
    public const F_DESTINATION_LOCATION = 'destinationLocation';
    public const F_VEHICLE_CLASS = 'vehicleClass';
    public const F_STATUS = 'status';
    public const F_CREATED_AT = 'createdAt';
    public const F_FINISHED_AT = 'finishedAt';
    public const F_ESTIMATED_PRICE = 'estimatedPrice';

    #[Column(type: 'datetime', name: TaxiRequestTable::CREATED_AT)]
    public \DateTimeImmutable $createdAt;

    #[Column(type: 'datetime', name: TaxiRequestTable::FINISHED_AT, nullable: true, default: null)]
    public ?\DateTimeImmutable $finishedAt = null;

    #[Column(type: 'uuid', name: TaxiRequestTable::DRIVER_UUID, nullable: true, typecast: 'uuid')]
    public ?UuidInterface $driverUuid = null;

    #[Column(type: 'string', name: TaxiRequestTable::STATUS, typecast: TaxiRequestStatus::class)]
    public TaxiRequestStatus $status;

    #[HasOne(target: Trip::class, innerKey: self::F_TRIP_UUID, outerKey: Trip::F_UUID, nullable: true)]
    public ?Trip $trip = null;

    public function __construct(
        #[Column(type: 'uuid', name: TaxiRequestTable::UUID, primary: true, typecast: 'uuid')]
        public UuidInterface $uuid,
        #[Column(type: 'uuid', name: TaxiRequestTable::USER_UUID, typecast: 'uuid')]
        public UuidInterface $userUuid,
        #[Column(type: 'jsonb', name: TaxiRequestTable::CURRENT_LOCATION)]
        public Location $currentLocation,
        #[Column(type: 'jsonb', name: TaxiRequestTable::DESTINATION_LOCATION)]
        public Location $destinationLocation,
        #[Column(type: 'string', name: TaxiRequestTable::VEHICLE_CLASS, typecast: VehicleClass::class)]
        public VehicleClass $vehicleClass,
        #[Column(type: 'float', name: TaxiRequestTable::ESTIMATED_PRICE)]
        public float $estimatedPrice,
    ) {
        $this->status = TaxiRequestStatus::Pending;
        $this->createdAt = new \DateTimeImmutable();
    }

    public function isPending(): bool
    {
        return $this->status === TaxiRequestStatus::Pending;
    }

    public function isAccepted(): bool
    {
        return $this->status === TaxiRequestStatus::Accepted;
    }

    public function accept(UuidInterface $driverUuid): void
    {
        $this->status = TaxiRequestStatus::Accepted;
        $this->driverUuid = $driverUuid;
    }

    public function cancel(string $reason): void
    {
        $this->status = TaxiRequestStatus::Cancelled;
    }

    public function complete(\DateTimeImmutable $endTime): void
    {
        $this->status = TaxiRequestStatus::Completed;
        $this->finishedAt = $endTime;
    }

    public function startTrip(): Trip
    {
        if (!$this->isAccepted()) {
            throw new InvalidRequestStatusException('Cannot start trip for a request that is not accepted');
        }

        if ($this->driverUuid === null) {
            throw new DriverUnavailableException('Cannot start trip without a driver');
        }

        $this->trip = new Trip(
            uuid: Uuid::uuid7(),
            taxiRequestUuid: $this->uuid,
            startTime: new \DateTimeImmutable(),
        );

        $this->status = TaxiRequestStatus::InProgress;

        return $trip;
    }
}
