<?php

declare(strict_types=1);

namespace Taxi;

use App\Infrastructure\CycleORM\Table\DriverTable;
use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Relation\HasMany;
use Cycle\Annotated\Annotation\Relation\HasOne;
use Ramsey\Uuid\UuidInterface;
use Taxi\Repository\DriverRepositoryInterface;

#[Entity(
    role: Driver::ROLE,
    repository: DriverRepositoryInterface::class,
    table: DriverTable::TABLE_NAME
)]
class Driver
{
    public const ROLE = 'driver';

    public const F_UUID = 'uuid';
    public const F_NAME = 'name';
    public const F_PHONE = 'phone';
    public const F_IS_AVAILABLE = 'isAvailable';
    public const F_CURRENT_LOCATION = 'currentLocation';
    public const F_CREATED_AT = 'createdAt';

    /** @var Rating[] */
    #[HasMany(target: Rating::class, innerKey: Driver::F_UUID, outerKey: Rating::F_RECIPIENT_UUID)]
    private array $ratings = [];

    #[Column(type: 'datetime', name: DriverTable::CREATED_AT)]
    public \DateTimeInterface $createdAt;

    /**
     * @param string $name Driver's full name
     * @param string $phone Driver's phone number
     * @param Vehicle $vehicle Driver's vehicle
     * @param bool $isAvailable Whether the driver is available for a ride (If driver accepts the ride, this will be set to false)
     */
    public function __construct(
        #[Column(type: 'uuid', name: DriverTable::UUID, primary: true, typecast: 'uuid')]
        public UuidInterface $uuid,
        #[Column(type: 'string', name: DriverTable::NAME)]
        public string $name,
        #[Column(type: 'string', name: DriverTable::PHONE)]
        public string $phone,
        #[HasOne(target: Vehicle::class, innerKey: Driver::F_UUID, outerKey: Vehicle::F_DRIVER_UUID)]
        public Vehicle $vehicle,
        #[Column(type: 'boolean', name: DriverTable::IS_AVAILABLE)]
        public bool $isAvailable = true,
        #[Column(type: 'jsonb', name: DriverTable::CURRENT_LOCATION, typecast: Location::class)]
        public ?Location $currentLocation = null,
    ) {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function addRating(Rating $rating): void
    {
        $this->ratings[] = $rating;
    }

    public function getAverageRating(): float
    {
        if ($this->ratings === []) {
            return 0;
        }

        $ratings = $this->ratings;

        return \array_sum(\array_map(fn(Rating $r) => $r->rating, $ratings)) / \count($ratings);
    }
}
