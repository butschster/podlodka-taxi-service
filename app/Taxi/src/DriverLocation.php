<?php

declare(strict_types=1);

namespace Taxi;

final class DriverLocation
{
    public function __construct(
        public Location $location,
        public \DateTimeImmutable $timestamp,
    ) {}
}
