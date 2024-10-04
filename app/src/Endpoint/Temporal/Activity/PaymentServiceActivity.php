<?php

declare(strict_types=1);

namespace App\Endpoint\Temporal\Activity;

use App\Endpoint\Temporal\Workflow\DTO\BlockFundsRequest;
use App\Endpoint\Temporal\Workflow\DTO\RefundRequest;
use Payment\Exception\InsufficientFundsException;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Temporal\Activity\ActivityInterface;
use Temporal\Activity\ActivityMethod;
use Temporal\Support\VirtualPromise;

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
                \sprintf(
                    'У вас недостаточно средств для оплаты заказа. Требуется: %s',
                    \number_format($request->amount, 2),
                ),
            );
        }

        dump('Блокировка средств на поездку.');

        return Uuid::uuid7();
    }

    #[ActivityMethod]
    public function refundFunds(RefundRequest $request): void
    {
        dump('Возврат средств');
    }

    #[ActivityMethod]
    public function commitFunds(UuidInterface $transactionUuid): void
    {
        dump('Оплата поездки прошла успешно.');
    }
}
