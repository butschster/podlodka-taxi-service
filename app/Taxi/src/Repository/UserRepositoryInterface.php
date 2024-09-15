<?php

declare(strict_types=1);

namespace Taxi\Repository;

use Cycle\ORM\RepositoryInterface;
use Ramsey\Uuid\UuidInterface;
use Taxi\Exception\UserNotFoundException;
use Taxi\User;

interface UserRepositoryInterface extends RepositoryInterface
{
    /**
     * Find user by UUID.
     */
    public function findByUuid(UuidInterface $uuid): ?User;

    /**
     * Get user by UUID.
     *
     * @throws UserNotFoundException
     */
    public function getByUuid(UuidInterface $uuid): User;

    /**
     * Persist user.
     */
    public function persist(User $vehicle): void;
}
