<?php

declare(strict_types=1);

namespace Taxi;

use Ramsey\Uuid\UuidInterface;

final class Trip
{
    public ?\DateTimeImmutable $endTime = null;
    public ?float $finalPrice = null;

    public function __construct(
        public readonly UuidInterface $uuid,
        public readonly TaxiRequest $request,
        public readonly \DateTimeImmutable $startTime,
        public ?Rating $userRating = null,
        public ?Rating $driverRating = null,
    ) {}

    public function finish(\DateTimeImmutable $endTime, float $finalPrice): void
    {
        $this->endTime = $endTime;
        $this->finalPrice = $finalPrice;
    }
}
