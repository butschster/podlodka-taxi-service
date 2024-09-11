<?php

declare(strict_types=1);

namespace App\Endpoint\Temporal\Workflow\DTO;

final class CancelRequest
{
    public function __construct(
        public string $reason,
    ) {}
}
