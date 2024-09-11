<?php

declare(strict_types=1);

namespace Taxi;

final class Rating
{
    public function __construct(
        public int $value,
        public ?string $comment = null,
    ) {}
}
