<?php

declare(strict_types=1);

namespace Taxi\Repository;

use Ramsey\Uuid\UuidInterface;
use Taxi\Exception\TaxiRequestNotFoundException;
use Taxi\TaxiRequest;

interface TaxiRequestRepositoryInterface
{
    public function findByUuid(UuidInterface $uuid): ?TaxiRequest;

    /**
     * @throws TaxiRequestNotFoundException
     */
    public function getByUuid(UuidInterface $uuid): TaxiRequest;


    public function persist(TaxiRequest $taxiRequest): void;
}
