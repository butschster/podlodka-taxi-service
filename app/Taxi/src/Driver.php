<?php

declare(strict_types=1);

namespace Taxi;

use App\Infrastructure\CycleORM\Table\DriverTable;
use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Relation\BelongsTo;
use Cycle\Annotated\Annotation\Relation\HasMany;
use Doctrine\Common\Collections\ArrayCollection;
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
    public const F_VEHICLE_UUID = 'vehicleUuid';
    public const F_NAME = 'name';
    public const F_PHONE = 'phone';
    public const F_IS_AVAILABLE = 'isAvailable';
    public const F_CURRENT_LOCATION = 'currentLocation';
    public const F_CREATED_AT = 'createdAt';

    /** @var ArrayCollection<Rating> */
    #[HasMany(target: Rating::class, innerKey: Driver::F_UUID, outerKey: Rating::F_RECIPIENT_UUID)]
    private ArrayCollection $ratings;

    #[Column(type: 'boolean', name: DriverTable::IS_AVAILABLE)]
    public bool $isAvailable = true;

    #[Column(type: 'datetime', name: DriverTable::CREATED_AT)]
    public \DateTimeInterface $createdAt;

    #[Column(type: 'jsonb', name: DriverTable::CURRENT_LOCATION, nullable: true, default: null, typecast: Location::class)]
    public ?Location $currentLocation = null;

    /**
     * @param string $name Driver's full name
     * @param string $phone Driver's phone number
     * @param Vehicle $vehicle Driver's vehicle
     */
    public function __construct(
        #[Column(type: 'uuid', name: DriverTable::UUID, primary: true, typecast: 'uuid')]
        public UuidInterface $uuid,
        #[Column(type: 'string', name: DriverTable::NAME)]
        public string $name,
        #[Column(type: 'string', name: DriverTable::PHONE)]
        public string $phone,
        #[BelongsTo(target: Vehicle::class, innerKey: Driver::F_VEHICLE_UUID, outerKey: Vehicle::F_UUID)]
        public Vehicle $vehicle,
    ) {
        $this->ratings = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
    }

    public function addRating(Rating $rating): void
    {
        $this->ratings->add($rating);
    }
}
