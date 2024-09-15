<?php

declare(strict_types=1);

namespace App\Infrastructure\CycleORM\Table;

final readonly class DriverTable
{
    public const TABLE_NAME = 'drivers';

    public const UUID = 'id';
    public const NAME = 'name';
    public const PHONE = 'phone';
    public const IS_AVAILABLE = 'is_available';
    public const CURRENT_LOCATION = 'current_location';
    public const CREATED_AT = 'created_at';
}
