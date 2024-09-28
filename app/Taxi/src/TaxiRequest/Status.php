<?php

declare(strict_types=1);

namespace Taxi\TaxiRequest;

use App\Infrastructure\CycleORM\Table\StatusTable;
use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Ramsey\Uuid\UuidInterface;
use Taxi\TaxiRequestStatus;
use Taxi\Repository\StatusRepositoryInterface;

#[Entity(
    role: Status::ROLE,
    repository: StatusRepositoryInterface::class,
    table: StatusTable::TABLE_NAME
)]
class Status
{
    public const ROLE = 'taxi_request_status';

    public const F_UUID = 'uuid';
    public const F_STATUS = 'status';
    public const F_REASON = 'reason';
    public const F_CREATED_AT = 'createdAt';
    public const F_TAXI_REQUEST_UUID = 'taxiRequestUuid';

    #[Column(type: 'datetime', name: StatusTable::CREATED_AT)]
    public \DateTimeImmutable $createdAt;

    public function __construct(
        #[Column(type: 'uuid', name: StatusTable::UUID, primary: true, typecast: 'uuid')]
        public UuidInterface $uuid,
        #[Column(type: 'uuid', name: StatusTable::TAXI_REQUEST_UUID, typecast: 'uuid')]
        public UuidInterface $taxiRequestUuid,
        #[Column(type: 'string', name: StatusTable::STATUS, typecast: TaxiRequestStatus::class)]
        public TaxiRequestStatus $status,
        #[Column(type: 'text', name: StatusTable::REASON, nullable: true)]
        public ?string $reason = null,
    ) {
        $this->createdAt = new \DateTimeImmutable();
    }
}
