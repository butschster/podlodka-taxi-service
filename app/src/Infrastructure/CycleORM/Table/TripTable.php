<?php

declare(strict_types=1);

namespace App\Infrastructure\CycleORM\Table;

final readonly class TripTable
{
    public const TABLE_NAME = 'trips';

    public const UUID = 'uuid';
    public const TAXI_REQUEST_UUID = 'taxi_request_uuid';
    public const USER_RATING_UUID = 'user_rating_uuid';
    public const DRIVER_RATING_UUID = 'driver_rating_uuid';
    public const START_TIME = 'start_time';
    public const END_TIME = 'end_time';
    public const FINAL_PRICE = 'final_price';
}
