<?php

declare(strict_types=1);

namespace App\Infrastructure\CycleORM\Table;

final class StatusTable
{
    public const TABLE_NAME = 'taxi_request_statuses';
    public const UUID = 'uuid';
    public const STATUS = 'status';
    public const REASON = 'reason';
    public const CREATED_AT = 'created_at';
    public const TAXI_REQUEST_UUID = 'taxi_request_uuid';
}
