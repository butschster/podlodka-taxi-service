<?php

declare(strict_types=1);

namespace Taxi;

use Ramsey\Uuid\UuidInterface;

final class Driver extends User
{
    public function __construct(
        UuidInterface $uuid,
        string $name,
        string $phone,
        public readonly Vehicle $vehicle,
        public bool $isAvailable = true,
    ) {
        parent::__construct($uuid, $name, $phone);
    }
}
