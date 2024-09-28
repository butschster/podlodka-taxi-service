<?php

declare(strict_types=1);

namespace Database\Seeder;

use Database\Factory\DriverFactory;
use Database\Factory\UserFactory;
use Spiral\DatabaseSeeder\Attribute\Seeder;
use Spiral\DatabaseSeeder\Seeder\AbstractSeeder;

#[Seeder]
class DataSeeder extends AbstractSeeder
{
    public function run(): \Generator
    {
        yield UserFactory::new()->createOne();

        yield DriverFactory::new()->createOne();
        yield DriverFactory::new()->createOne();
        yield DriverFactory::new()->createOne();
    }
}
