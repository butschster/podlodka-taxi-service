<?php

declare(strict_types=1);

namespace Taxi;

final class Location implements \JsonSerializable, \Stringable
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

    public static function typecast(string $value): self
    {
        $value = \json_decode($value, true);

        return new self($value['latitude'], $value['longitude']);
    }

    public function __toString(): string
    {
        return \json_encode($this);
    }
}
