<?php

declare(strict_types=1);

namespace App\Endpoint\Temporal\Workflow\DTO;

use Ramsey\Uuid\UuidInterface;
use Taxi\DriverLocation;

final class AcceptRequest
{
    public function __construct(
        public UuidInterface $driverUuid,
        public DriverLocation $currentLocation,
    ) {}
}
