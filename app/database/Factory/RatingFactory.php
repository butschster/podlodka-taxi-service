<?php

declare(strict_types=1);

namespace Database\Factory;

use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Spiral\DatabaseSeeder\Factory\AbstractFactory;
use Taxi\Rating;

/**
 * @extends AbstractFactory<Rating>
 */
class RatingFactory extends AbstractFactory
{
    /**
     * Returns a fully qualified database entity class name
     */
    public function entity(): string
    {
        return Rating::class;
    }

    /**
     * Returns array with generation rules
     */
    public function definition(): array
    {
        return [
            'uuid' => Uuid::uuid7(),
            'rating' => $this->faker->numberBetween(1, 5),
            'comment' => $this->faker->text(),
        ];
    }

    public function makeEntity(array $definition): object
    {
        return new Rating(
            uuid: $definition['uuid'],
            tripUuid: $definition['tripUuid'],
            recipientUuid: $definition['recipientUuid'],
            rating: $definition['rating'],
            comment: $definition['comment'],
        );
    }

    public function withTripUuid(UuidInterface $tripUuid): self
    {
        return $this->state(fn(\Faker\Generator $faker, array $definition) => [
            Rating::F_TRIP_UUID => $tripUuid,
        ]);
    }

    public function withRecipientUuid(UuidInterface $recipientUuid): self
    {
        return $this->state(fn(\Faker\Generator $faker, array $definition) => [
            Rating::F_RECIPIENT_UUID => $recipientUuid,
        ]);
    }
}
