<?php

declare(strict_types=1);

namespace Taxi;

class Vehicle
{
    public function __construct(
        public string $licensePlate,
        public string $model,
        public VehicleClass $class,
    ) {}
}
