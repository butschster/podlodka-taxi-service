<?php

declare(strict_types=1);

namespace App\Endpoint\Temporal\Workflow\DTO;

use Ramsey\Uuid\UuidInterface;
use Taxi\DriverLocation;

final readonly class FinishRequest
{
    public function __construct(
        public UuidInterface $tripUuid,
        public \DateTimeImmutable $time,
        public ?DriverLocation $location,
    ) {}
}
