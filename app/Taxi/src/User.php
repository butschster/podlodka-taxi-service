<?php

declare(strict_types=1);

namespace Taxi;

use App\Infrastructure\CycleORM\Table\DriverTable;
use App\Infrastructure\CycleORM\Table\UserTable;
use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Relation\HasMany;
use Ramsey\Uuid\UuidInterface;
use Taxi\Repository\UserRepositoryInterface;

#[Entity(
    role: User::ROLE,
    repository: UserRepositoryInterface::class,
    table: UserTable::TABLE_NAME
)]
class User
{
    public const ROLE = 'user';

    public const F_UUID = 'uuid';
    public const F_NAME = 'name';
    public const F_PHONE = 'phone';
    public const F_CREATED_AT = 'createdAt';

    /** @var Rating[] */
    #[HasMany(target: Rating::class, innerKey: User::F_UUID, outerKey: Rating::F_RECIPIENT_UUID)]
    private array $ratings = [];

    #[Column(type: 'datetime', name: DriverTable::CREATED_AT)]
    public \DateTimeInterface $createdAt;

    public function __construct(
        #[Column(type: 'uuid', name: DriverTable::UUID, primary: true, typecast: 'uuid')]
        public UuidInterface $uuid,
        #[Column(type: 'string', name: DriverTable::NAME)]
        public string $name,
        #[Column(type: 'string', name: DriverTable::PHONE)]
        public string $phone,
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
