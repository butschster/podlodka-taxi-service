<?php

declare(strict_types=1);

namespace Taxi;

use App\Infrastructure\CycleORM\Table\TripTable;
use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Ramsey\Uuid\UuidInterface;
use Taxi\Repository\TripRepositoryInterface;

#[Entity(
    role: Trip::ROLE,
    repository: TripRepositoryInterface::class,
    table: TripTable::TABLE_NAME
)]
class Trip
{
    public const ROLE = 'trip';

    public const F_UUID = 'uuid';
    public const F_TAXI_REQUEST_UUID = 'taxiRequestUuid';
    public const F_USER_RATING_UUID = 'userRatingUuid';
    public const F_DRIVER_RATING_UUID = 'driverRatingUuid';
    public const F_START_TIME = 'startTime';
    public const F_END_TIME = 'endTime';
    public const F_FINAL_PRICE = 'finalPrice';

    #[Column(type: 'datetime', name: TripTable::END_TIME, nullable: true, default: null)]
    public ?\DateTimeImmutable $endTime = null;

    #[Column(type: 'float', name: TripTable::FINAL_PRICE, nullable: true, default: null)]
    public ?float $finalPrice = null;

    #[Column(type: 'uuid', name: TripTable::USER_RATING_UUID, nullable: true, default: null, typecast: 'uuid')]
    public ?UuidInterface $userRatingUuid = null;

    #[Column(type: 'uuid', name: TripTable::DRIVER_RATING_UUID, nullable: true, default: null, typecast: 'uuid')]
    public ?UuidInterface $driverRatingUuid = null;

    /**
     * This class represents a taxi trip.
     *
     * @param UuidInterface $uuid Trip's UUID
     * @param UuidInterface $taxiRequestUuid Taxi request
     * @param \DateTimeImmutable $startTime Trip's start time
     */
    public function __construct(
        #[Column(type: 'uuid', name: TripTable::UUID, primary: true, typecast: 'uuid')]
        public UuidInterface $uuid,
        #[Column(type: 'uuid', name: TripTable::TAXI_REQUEST_UUID, typecast: 'uuid')]
        public UuidInterface $taxiRequestUuid,
        #[Column(type: 'datetime', name: TripTable::START_TIME)]
        public \DateTimeImmutable $startTime,
    ) {}

    public function finish(\DateTimeImmutable $endTime, float $finalPrice): void
    {
        $this->endTime = $endTime;
        $this->finalPrice = $finalPrice;
    }
}
