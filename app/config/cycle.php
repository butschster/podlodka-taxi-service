<?php

declare(strict_types=1);

use App\Infrastructure\CycleORM\Typecaster\UuidTypecast;
use Cycle\ORM\Collection\ArrayCollectionFactory;
use Cycle\ORM\Collection\DoctrineCollectionFactory;
use Cycle\ORM\Parser\Typecast;
use Cycle\ORM\SchemaInterface;

return [
    'schema' => [
        'cache' => env('CYCLE_SCHEMA_CACHE', true),
        'defaults' => [
            SchemaInterface::TYPECAST_HANDLER => [
                UuidTypecast::class,
                Typecast::class,
            ],
        ],

        'collections' => [
            'default' => 'doctrine',
            'factories' => ['doctrine' => new DoctrineCollectionFactory()],
        ],

        'generators' => null,
    ],

    'warmup' => env('CYCLE_SCHEMA_WARMUP', false),
];
