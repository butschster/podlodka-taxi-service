<?php

declare(strict_types=1);

namespace App\Infrastructure\CycleORM\Repository;

use Cycle\ORM\EntityManagerInterface;
use Cycle\ORM\Select;
use Cycle\ORM\Select\Repository;
use Ramsey\Uuid\UuidInterface;
use Taxi\Exception\UserNotFoundException;
use Taxi\Repository\UserRepositoryInterface;
use Taxi\User;

/**
 * @extends Repository<User>
 */
final class UserRepository extends Repository implements UserRepositoryInterface
{
    public function __construct(
        Select $select,
        private readonly EntityManagerInterface $em,
    ) {
        parent::__construct($select);
    }

    public function findByUuid(UuidInterface $uuid): ?User
    {
        return $this->select()->where(User::F_UUID, $uuid)->fetchOne();
    }

    public function getByUuid(UuidInterface $uuid): User
    {
        $user = $this->findByUuid($uuid);

        if ($user === null) {
            throw new UserNotFoundException(\sprintf('User with UUID %s not found', $uuid->toString()));
        }

        return $user;
    }

    public function persist(User $vehicle): void
    {
        $this->em->persist($vehicle);
    }
}
