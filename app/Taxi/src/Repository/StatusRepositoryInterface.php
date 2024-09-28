<?php

declare(strict_types=1);

namespace Taxi\Repository;

use Cycle\ORM\RepositoryInterface;
use Taxi\TaxiRequest\Status;

interface StatusRepositoryInterface extends RepositoryInterface
{
    public function persist(Status $status): void;
}
