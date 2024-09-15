<?php

declare(strict_types=1);

namespace Taxi\Repository;

use Cycle\ORM\RepositoryInterface;
use Taxi\Rating;

interface RatingRepositoryInterface extends RepositoryInterface
{
    /**
     * Persist rating data.
     */
    public function persist(Rating $rating): void;
}
