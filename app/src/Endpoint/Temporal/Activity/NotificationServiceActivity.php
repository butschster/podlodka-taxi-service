<?php

declare(strict_types=1);

namespace App\Endpoint\Temporal\Activity;

use App\Endpoint\Temporal\Workflow\TaskQueue;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\UuidInterface;
use Spiral\TemporalBridge\Attribute\AssignWorker;
use Temporal\Activity\ActivityInterface;
use Temporal\Activity\ActivityMethod;
use Temporal\Support\VirtualPromise;

#[AssignWorker(TaskQueue::NOTIFICATION_SERVICE)]
#[ActivityInterface(prefix: "notification-request.")]
final readonly class NotificationServiceActivity
{
    public function __construct(
        private LoggerInterface $logger,
    ) {}

    /**
     * @return VirtualPromise<void>
     */
    #[ActivityMethod]
    public function newRequest(UuidInterface $taxiRequestUuid): void
    {
        // Send push notification to nearby drivers
        $this->logger->info('Sending push notification to nearby drivers');
    }

    /**
     * @return VirtualPromise<void>
     */
    #[ActivityMethod]
    public function userCanceled(UuidInterface $taxiRequestUuid): void
    {
        // Send push notification to nearby drivers
        $this->logger->info('Request was canceled by the user');
    }

    /**
     * @return VirtualPromise<void>
     */
    #[ActivityMethod]
    public function driverAccepted(UuidInterface $taxiRequestUuid, UuidInterface $driverUuid): void
    {
        // Send push notification to user
        $this->logger->info('Driver accepted the request');
    }

    /**
     * @return VirtualPromise<void>
     */
    #[ActivityMethod]
    public function noDriverAvailable(UuidInterface $taxiRequestUuid): void
    {
        // Send push notification to user
        $this->logger->info('No driver available');
    }

    /**
     * @return VirtualPromise<void>
     */
    #[ActivityMethod]
    public function driverArrived(UuidInterface $taxiRequestUuid): void
    {
        // Send push notification to user
        $this->logger->info('Driver arrived');
    }

    /**
     * @return VirtualPromise<void>
     */
    #[ActivityMethod]
    public function driverMatchFailed(UuidInterface $taxiRequestUuid, UuidInterface $driverUuid): void
    {
        // Send push notification to driver that he was not selected
        $this->logger->info('Driver was not selected');
    }

    /**
     * @return VirtualPromise<void>
     */
    #[ActivityMethod]
    public function stillSearching(UuidInterface $taxiRequestUuid): void
    {
        // Send push notification to user
        $this->logger->info('Still searching for a driver');
    }

    /**
     * @return VirtualPromise<void>
     */
    #[ActivityMethod]
    public function insufficientFunds(UuidInterface $taxiRequestUuid): void
    {
        // Send push notification to user
        $this->logger->info('Insufficient funds');
    }

    /**
     * @return VirtualPromise<void>
     */
    #[ActivityMethod]
    public function tripFinished(UuidInterface $taxiRequestUuid): void
    {
        // Send push notification to user
        $this->logger->info('Trip finished');
    }

    /**
     * @return VirtualPromise<void>
     */
    #[ActivityMethod]
    public function driverNotResponding(UuidInterface $taxiRequestUuid): void
    {
        // Send push notification to a manager to call the driver
        $this->logger->info('Driver is not responding');
    }
}
