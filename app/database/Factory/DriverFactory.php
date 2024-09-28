<?php

declare(strict_types=1);

namespace Database\Factory;

use Ramsey\Uuid\Uuid;
use Spiral\DatabaseSeeder\Factory\AbstractFactory;
use Taxi\Driver;

/**
 * @extends AbstractFactory<Driver>
 */
class DriverFactory extends AbstractFactory
{
    public function entity(): string
    {
        return Driver::class;
    }

    public function definition(): array
    {
        return [
            'uuid' => Uuid::uuid7(),
            'name' => $this->faker->name(),
            'phone' => $this->faker->phoneNumber(),
            'vehicle' => fn() => VehicleFactory::new()->createOne(),
        ];
    }

    public function makeEntity(array $definition): object
    {
        $driver = new Driver(
            uuid: $definition['uuid'],
            name: $definition['name'],
            phone: $definition['phone'],
            vehicle: $definition['vehicle'],
        );

        return $driver;
    }
}
