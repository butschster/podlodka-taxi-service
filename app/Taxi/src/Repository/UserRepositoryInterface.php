<?php

declare(strict_types=1);

namespace Taxi\Repository;

use Ramsey\Uuid\UuidInterface;
use Taxi\Exception\UserNotFoundException;
use Taxi\User;

interface UserRepositoryInterface
{
    public function findByUuid(UuidInterface $uuid): ?User;

    /**
     * @throws UserNotFoundException
     */
    public function getByUuid(UuidInterface $uuid): User;
}
