<?php

declare(strict_types=1);

namespace App\Endpoint\Temporal\Activity;

use App\Endpoint\Temporal\Workflow\DTO\BlockFundsRequest;
use App\Endpoint\Temporal\Workflow\DTO\RefundRequest;
use App\Endpoint\Temporal\Workflow\TaskQueue;
use Payment\Exception\InsufficientFundsException;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Spiral\TemporalBridge\Attribute\AssignWorker;
use Temporal\Activity\ActivityInterface;
use Temporal\Activity\ActivityMethod;
use Temporal\Support\VirtualPromise;

#[AssignWorker(TaskQueue::PAYMENT_SERVICE)]
#[ActivityInterface(prefix: "payment-request.")]
final class PaymentServiceActivity
{
    /**
     * @return VirtualPromise<UuidInterface>
     */
    #[ActivityMethod]
    public function blockFunds(BlockFundsRequest $request): UuidInterface
    {
        // rand 1 of 4 chance to throw an exception
        if (\rand(1, 4) === 1) {
            throw new InsufficientFundsException(
                \sprintf('Insufficient funds. You need at least %s', \number_format($request->amount, 2)),
            );
        }

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
