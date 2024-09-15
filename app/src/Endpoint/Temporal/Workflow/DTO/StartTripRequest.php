<?php

declare(strict_types=1);

namespace App\Endpoint\Temporal\Workflow\DTO;

use Ramsey\Uuid\UuidInterface;

final class StartTripRequest
{
    public function __construct(
        public UuidInterface $taxiRequestUuid,
    ) {
    }
}
