<?php

declare(strict_types=1);

namespace App\Endpoint\Temporal\Activity;

use App\Endpoint\Temporal\Workflow\DTO\BlockFundsRequest;
use App\Endpoint\Temporal\Workflow\DTO\RefundRequest;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Spiral\TemporalBridge\Attribute\AssignWorker;
use Temporal\Activity\ActivityInterface;
use Temporal\Activity\ActivityMethod;
use Temporal\Support\VirtualPromise;

#[AssignWorker('payment-service')]
#[ActivityInterface(prefix: "payment-request.")]
final class PaymentServiceActivity
{
    /**
     * @return VirtualPromise<UuidInterface>
     */
    #[ActivityMethod]
    public function blockFunds(BlockFundsRequest $request): UuidInterface
    {
        // ...
        // Transaction logic

        // Return transaction ID
        return Uuid::uuid7();
    }

    /**
     * @return VirtualPromise<void>
     */
    public function commitFunds(UuidInterface $transactionUuid): void
    {
        // ...
    }

    /**
     * @return VirtualPromise<void>
     */
    #[ActivityMethod]
    public function refundFunds(RefundRequest $request): void
    {
        // ...
        // Transaction logic

    }
}
