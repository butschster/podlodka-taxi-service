<?php

declare(strict_types=1);

namespace App\Endpoint\Temporal\Workflow\DTO;

use Ramsey\Uuid\UuidInterface;

final readonly class BlockFundsRequest
{
    public function __construct(
        public UuidInterface $operationUuid,
        public UuidInterface $userUuid,
        public float $amount,
    ) {}
}
