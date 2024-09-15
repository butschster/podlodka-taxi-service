<?php

declare(strict_types=1);

namespace Taxi;

use App\Infrastructure\CycleORM\Table\RatingTable;
use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Ramsey\Uuid\UuidInterface;
use Taxi\Repository\RatingRepositoryInterface;

#[Entity(
    role: Rating::ROLE,
    repository: RatingRepositoryInterface::class,
    table: RatingTable::TABLE_NAME
)]
class Rating
{
    public const ROLE = 'rating';

    public const F_UUID = 'uuid';
    public const F_TRIP_UUID = 'tripUuid';
    public const F_RECIPIENT_UUID = 'recipientUuid';
    public const F_RATING = 'rating';
    public const F_COMMENT = 'comment';
    public const F_CREATED_AT = 'createdAt';

    #[Column(type: 'datetime', name: RatingTable::CREATED_AT)]
    private \DateTimeImmutable $createdAt;

    /**
     * This class represents a rating given by a user to a driver and vice versa.
     *
     * @param int $rating Rating value (1-5)
     * @param string|null $comment Optional comment
     */
    public function __construct(
        #[Column(type: 'uuid', name: RatingTable::UUID, primary: true, typecast: 'uuid')]
        public UuidInterface $uuid,
        #[Column(type: 'uuid', name: RatingTable::TRIP_UUID, typecast: 'uuid')]
        public UuidInterface $tripUuid,
        #[Column(type: 'uuid', name: RatingTable::RECIPIENT_UUID, typecast: 'uuid')]
        public UuidInterface $recipientUuid,
        #[Column(type: 'integer', name: RatingTable::RATING)]
        public int $rating,
        #[Column(type: 'string', name: RatingTable::COMMENT, nullable: true, default: null)]
        public ?string $comment = null,
    ) {
        $this->createdAt = new \DateTimeImmutable();
    }
}
