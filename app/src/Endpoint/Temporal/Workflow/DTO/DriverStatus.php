<?php

declare(strict_types=1);

namespace App\Endpoint\Temporal\Workflow\DTO;

final readonly class DriverStatus
{
    public function __construct(
        public bool $isMatched,
        public ?string $reason = null,
    ) {}
}
