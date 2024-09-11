<?php

declare(strict_types=1);

namespace Taxi;

use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Taxi\Exception\DriverUnavailableException;
use Taxi\Exception\InvalidRequestStatusException;

final class TaxiRequest
{
    public readonly \DateTimeImmutable $createdAt;
    public ?\DateTimeImmutable $finishedAt = null;

    public function __construct(
        public readonly UuidInterface $uuid,
        public readonly User $user,
        public readonly Location $currentLocation,
        public readonly Location $destinationLocation,
        public readonly VehicleClass $vehicleClass,
        public float $estimatedPrice,
        public ?Driver $driver = null,
        public TaxiRequestStatus $status = TaxiRequestStatus::Pending,
        ?\DateTimeImmutable $createdAt = null,
    ) {
        $this->createdAt = $createdAt ?? new \DateTimeImmutable();
    }

    public function isPending(): bool
    {
        return $this->status === TaxiRequestStatus::Pending;
    }

    public function isAccepted(): bool
    {
        return $this->status === TaxiRequestStatus::Accepted;
    }

    public function isInProgress(): bool
    {
        return $this->status === TaxiRequestStatus::InProgress;
    }

    public function setDriver(Driver $driver): void
    {
        $this->driver = $driver;
    }

    public function accept(Driver $driver): void
    {
        $this->status = TaxiRequestStatus::Accepted;
        $this->driver = $driver;
    }

    public function cancel(string $reason): void
    {
        $this->status = TaxiRequestStatus::Cancelled;
    }

    public function complete(\DateTimeImmutable $endTime): void
    {
        $this->status = TaxiRequestStatus::Completed;
        $this->finishedAt = $endTime;
    }

    public function startTrip(): Trip
    {
        if (!$this->isAccepted()) {
            throw new InvalidRequestStatusException('Cannot start trip for a request that is not accepted');
        }

        if ($this->driver === null) {
            throw new DriverUnavailableException('Cannot start trip without a driver');
        }

        $trip = new Trip(
            uuid: Uuid::uuid7(),
            request: $this,
            startTime: new \DateTimeImmutable(),
        );

        $this->status = TaxiRequestStatus::InProgress;

        return $trip;
    }
}
