<?php

declare(strict_types=1);

namespace App\Infrastructure\CycleORM\Table;

final readonly class UserTable
{
    public const TABLE_NAME = 'users';

    public const UUID = 'id';
    public const NAME = 'name';
    public const PHONE = 'phone';
    public const CREATED_AT = 'created_at';
}
