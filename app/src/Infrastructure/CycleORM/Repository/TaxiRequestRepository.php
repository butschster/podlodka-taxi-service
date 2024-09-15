<?php

declare(strict_types=1);

namespace App\Infrastructure\CycleORM\Repository;

use Cycle\ORM\EntityManagerInterface;
use Cycle\ORM\Select;
use Cycle\ORM\Select\Repository;
use Ramsey\Uuid\UuidInterface;
use Taxi\Exception\TaxiRequestNotFoundException;
use Taxi\Repository\TaxiRequestRepositoryInterface;
use Taxi\TaxiRequest;

/**
 * @extends Repository<TaxiRequest>
 */
final class TaxiRequestRepository extends Repository implements TaxiRequestRepositoryInterface
{
    public function __construct(
        Select $select,
        private readonly EntityManagerInterface $em,
    ) {
        parent::__construct($select);
    }

    public function findByUuid(UuidInterface $uuid): ?TaxiRequest
    {
        return $this->select()->where(TaxiRequest::F_UUID, $uuid)->fetchOne();
    }

    public function getByUuid(UuidInterface $uuid): TaxiRequest
    {
        $taxiRequest = $this->findByUuid($uuid);
        if ($taxiRequest === null) {
            throw new TaxiRequestNotFoundException(\sprintf('Taxi request with UUID %s not found.', $uuid->toString()));
        }

        return $taxiRequest;
    }

    public function persist(TaxiRequest $taxiRequest): void
    {
        $this->em->persist($taxiRequest)->run();
    }
}
