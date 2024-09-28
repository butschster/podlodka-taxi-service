<?php

declare(strict_types=1);

namespace Database\Factory;

use Ramsey\Uuid\Uuid;
use Spiral\DatabaseSeeder\Factory\AbstractFactory;
use Taxi\Vehicle;
use Taxi\VehicleClass;

/**
 * @extends AbstractFactory<Vehicle>
 */
class VehicleFactory extends AbstractFactory
{
    /**
     * Returns a fully qualified database entity class name
     */
    public function entity(): string
    {
        return Vehicle::class;
    }

    /**
     * Returns array with generation rules
     */
    public function definition(): array
    {
        return [
            'uuid' => Uuid::uuid7(),
            'licensePlate' => $this->faker->unique()->regexify('[A-Z]{2}[0-9]{4}[A-Z]{2}'),
            'model' => $this->faker->word(),
            'class' => $this->faker->randomElement([
                VehicleClass::Business,
                VehicleClass::Economy,
                VehicleClass::Comfort,
            ]),
        ];
    }

    public function makeEntity(array $definition): object
    {
        return new Vehicle(
            uuid: $definition['uuid'],
            licensePlate: $definition['licensePlate'],
            model: $definition['model'],
            class: $definition['class'],
        );
    }
}
