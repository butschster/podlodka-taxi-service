<?php

declare(strict_types=1);

namespace App\Infrastructure\CycleORM\Table;

final readonly class RatingTable
{
    public const TABLE_NAME = 'rating';

    public const UUID = 'uuid';
    public const TRIP_UUID = 'trip_uuid';
    public const RECIPIENT_UUID = 'recipient_uuid';
    public const RATING = 'rating';
    public const COMMENT = 'comment';
    public const CREATED_AT = 'created_at';
}
