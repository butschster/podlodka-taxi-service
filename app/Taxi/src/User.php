<?php

declare(strict_types=1);

namespace Taxi;

use Ramsey\Uuid\UuidInterface;

class User
{
    /** @var Rating[] */
    private array $ratings = [];

    public function __construct(
        public readonly UuidInterface $uuid,
        public readonly string $name,
        public readonly string $phone,
    ) {}

    public function addRating(Rating $rating): void
    {
        $this->ratings[] = $rating;
    }

    public function getAverageRating(): float
    {
        if ($this->ratings === []) {
            return 0;
        }

        return \array_sum(\array_map(fn($r) => $r->value, $this->ratings)) / \count($this->ratings);
    }
}
