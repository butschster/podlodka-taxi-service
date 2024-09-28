<?php

declare(strict_types=1);

namespace App\Infrastructure\CycleORM\Repository;

use Cycle\ORM\EntityManagerInterface;
use Cycle\ORM\Select;
use Cycle\ORM\Select\Repository;
use Taxi\Repository\StatusRepositoryInterface;
use Taxi\TaxiRequest\Status;

/**
 * @extends Repository<Status>
 */
final class StatusRepository extends Repository implements StatusRepositoryInterface
{
    public function __construct(
        Select $select,
        private readonly EntityManagerInterface $em,
    ) {
        parent::__construct($select);
    }

    public function persist(Status $status): void
    {
        $this->em->persist($status)->run();
    }
}
