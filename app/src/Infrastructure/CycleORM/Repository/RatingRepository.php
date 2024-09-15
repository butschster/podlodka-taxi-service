<?php

declare(strict_types=1);

namespace App\Infrastructure\CycleORM\Repository;

use Cycle\ORM\EntityManagerInterface;
use Cycle\ORM\Select;
use Cycle\ORM\Select\Repository;
use Taxi\Rating;
use Taxi\Repository\RatingRepositoryInterface;

/**
 * @extends Repository<Rating>
 */
final class RatingRepository extends Repository implements RatingRepositoryInterface
{
    public function __construct(
        Select $select,
        private readonly EntityManagerInterface $em,
    ) {
        parent::__construct($select);
    }

    public function persist(Rating $rating): void
    {
        $this->em->persist($rating)->run();
    }
}
