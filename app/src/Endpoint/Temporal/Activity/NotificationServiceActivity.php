<?php

declare(strict_types=1);

namespace App\Endpoint\Temporal\Activity;

use Ramsey\Uuid\UuidInterface;
use Spiral\TemporalBridge\Attribute\AssignWorker;
use Taxi\TaxiRequest;
use Temporal\Activity\ActivityInterface;
use Temporal\Activity\ActivityMethod;
use Temporal\Support\VirtualPromise;

#[AssignWorker('notification-service')]
#[ActivityInterface(prefix: "notification-request.")]
final class NotificationServiceActivity
{
    /**
     * @return VirtualPromise<void>
     */
    #[ActivityMethod]
    public function newRequest(UuidInterface $taxiRequestUuid): void
    {
        // Send push notification to nearby drivers
    }

    /**
     * @return VirtualPromise<void>
     */
    #[ActivityMethod]
    public function userCanceled(UuidInterface $taxiRequestUuid): void
    {
        // Send push notification to nearby drivers
    }

    /**
     * @return VirtualPromise<void>
     */
    #[ActivityMethod]
    public function driverAccepted(UuidInterface $taxiRequestUuid, UuidInterface $driverUuid): void
    {
        // Send push notification to user
    }

    /**
     * @return VirtualPromise<void>
     */
    #[ActivityMethod]
    public function noDriverAvailable(UuidInterface $taxiRequestUuid): void
    {
        // Send push notification to user
    }

    /**
     * @return VirtualPromise<void>
     */
    #[ActivityMethod]
    public function driverArrived(UuidInterface $taxiRequestUuid): void
    {
        // Send push notification to user
    }

    /**
     * @return VirtualPromise<void>
     */
    #[ActivityMethod]
    public function driverMatchFailed(UuidInterface $taxiRequestUuid, UuidInterface $driverUuid): void
    {
        // Send push notification to driver that he was not selected
    }

}
