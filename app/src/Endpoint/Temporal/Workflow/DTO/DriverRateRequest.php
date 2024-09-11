<?php

declare(strict_types=1);

namespace App\Endpoint\Temporal\Workflow\DTO;

final readonly class DriverRateRequest
{
    public function __construct(
        public int $rating,
        public string $comment,
    ) {}
}