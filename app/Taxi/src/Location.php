<?php

declare(strict_types=1);

namespace Taxi;

final class Location implements \JsonSerializable
{
    public function __construct(
        public float $latitude,
        public float $longitude,
    ) {}

    public function jsonSerialize(): array
    {
        return [
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
        ];
    }
}
