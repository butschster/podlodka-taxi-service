<?php

declare(strict_types=1);

namespace Taxi;

use Taxi\Exception\InvalidStatusTransitionException;

final class StatusTransitionValidator
{
    private const array ALLOWED_TRANSITIONS = [
        TaxiRequestStatus::Pending->value => [
            TaxiRequestStatus::Accepted->value,
            TaxiRequestStatus::Cancelled->value,
        ],
        TaxiRequestStatus::Accepted->value => [
            TaxiRequestStatus::InProgress->value,
            TaxiRequestStatus::Cancelled->value,
        ],
        TaxiRequestStatus::InProgress->value => [
            TaxiRequestStatus::Completed->value,
            TaxiRequestStatus::Cancelled->value,
        ],
        TaxiRequestStatus::Completed->value => [],
        TaxiRequestStatus::Cancelled->value => [],
    ];

    public static function validate(TaxiRequestStatus $currentStatus, TaxiRequestStatus $newStatus): void
    {
        if (!self::isValidTransition($currentStatus, $newStatus)) {
            throw new InvalidStatusTransitionException(
                \sprintf(
                    'Invalid status transition from %s to %s',
                    $currentStatus->value,
                    $newStatus->value,
                ),
            );
        }
    }

    private static function isValidTransition(TaxiRequestStatus $currentStatus, TaxiRequestStatus $newStatus): bool
    {
        return \in_array($newStatus->value, self::ALLOWED_TRANSITIONS[$currentStatus->value], true);
    }
}
