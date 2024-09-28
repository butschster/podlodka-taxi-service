<?php

declare(strict_types=1);

namespace App\Infrastructure\CycleORM\Table;

final readonly class VehicleTable
{
    public const TABLE_NAME = 'vehicles';

    public const UUID = 'uuid';
    public const LICENSE_PLATE = 'license_plate';
    public const MODEL = 'model';
    public const VEHICLE_CLASS = 'vehicle_class';
    public const CREATED_AT = 'created_at';
}
