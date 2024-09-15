<?php

declare(strict_types=1);

namespace Taxi\Repository;

use Cycle\ORM\RepositoryInterface;
use Ramsey\Uuid\UuidInterface;
use Taxi\Exception\TaxiRequestNotFoundException;
use Taxi\TaxiRequest;

interface TaxiRequestRepositoryInterface extends RepositoryInterface
{
    /**
     * Find taxi request by UUID.
     */
    public function findByUuid(UuidInterface $uuid): ?TaxiRequest;

    /**
     * Get taxi request by UUID.
     *
     * @throws TaxiRequestNotFoundException
     */
    public function getByUuid(UuidInterface $uuid): TaxiRequest;

    /**
     * Persist taxi request data.
     */
    public function persist(TaxiRequest $taxiRequest): void;
}
