<?php

declare(strict_types=1);

namespace App\Infrastructure\CycleORM\Table;

final readonly class TaxiRequestTable
{
    public const TABLE_NAME = 'taxi_requests';

    public const UUID = 'uuid';
    public const TRIP_UUID = 'trip_uuid';
    public const USER_UUID = 'user_uuid';
    public const DRIVER_UUID = 'driver_uuid';
    public const CURRENT_LOCATION = 'current_location';
    public const DESTINATION_LOCATION = 'destination_location';
    public const VEHICLE_CLASS = 'vehicle_class';
    public const STATUS = 'status';
    public const CREATED_AT = 'created_at';
    public const FINISHED_AT = 'finished_at';
    public const ESTIMATED_PRICE = 'estimated_price';
}
