<?php

declare(strict_types=1);

namespace App\Endpoint\Temporal\Workflow\DTO;

use Ramsey\Uuid\UuidInterface;

final readonly class RefundRequest
{
    public function __construct(
        public UuidInterface $transactionUuid,
    ) {}
}
