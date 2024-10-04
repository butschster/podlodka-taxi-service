<?php

declare(strict_types=1);

namespace App\Endpoint\Temporal\Workflow\DTO;

use Ramsey\Uuid\UuidInterface;
use Taxi\Location;
use Taxi\VehicleClass;

final readonly class CreateRequest
{
    public function __construct(
        public UuidInterface $requestUuid,
        public UuidInterface $userUuid,
        public Location $currentLocation,
        public Location $destinationLocation,
        public VehicleClass $vehicleClass,
    ) {}
}
