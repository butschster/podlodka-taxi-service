<?php

declare(strict_types=1);

namespace Taxi;

final class DriverLocation
{
    /**
     * @param Location $location Driver's current location
     * @param \DateTimeImmutable $timestamp Timestamp when the location was recorded
     */
    public function __construct(
        public Location $location,
        public \DateTimeImmutable $timestamp,
    ) {}
}
