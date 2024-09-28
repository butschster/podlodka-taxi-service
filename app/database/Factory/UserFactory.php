<?php

declare(strict_types=1);

namespace Database\Factory;

use Ramsey\Uuid\Uuid;
use Spiral\DatabaseSeeder\Factory\AbstractFactory;
use Taxi\User;

/**
 * @extends AbstractFactory<User>
 */
class UserFactory extends AbstractFactory
{
    public function entity(): string
    {
        return User::class;
    }

    public function definition(): array
    {
        return [
            'uuid' => Uuid::uuid7(),
            'name' => $this->faker->name(),
            'phone' => $this->faker->phoneNumber(),
        ];
    }

    public function makeEntity(array $definition): object
    {
        $user = new User(
            uuid: $definition['uuid'],
            name: $definition['name'],
            phone: $definition['phone'],
        );

        return $user;
    }
}
