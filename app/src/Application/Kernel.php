<?php

declare(strict_types=1);

namespace App\Application;

use Spiral\Boot\Bootloader\CoreBootloader;
use Spiral\DotEnv\Bootloader\DotenvBootloader;
use Spiral\Prototype\Bootloader\PrototypeBootloader;
use Spiral\Tokenizer\Bootloader\TokenizerListenerBootloader;

class Kernel extends \Spiral\Framework\Kernel
{
    public function defineSystemBootloaders(): array
    {
        return [
            CoreBootloader::class,
            DotenvBootloader::class,
            TokenizerListenerBootloader::class,
        ];
    }

    public function defineBootloaders(): array
    {
        return [
            // Infrastructure
            Bootloader\Infrastructure\LogsBootloader::class,
            Bootloader\Infrastructure\ConsoleBootloader::class,
            Bootloader\Infrastructure\RoadRunnerBootloader::class,
            Bootloader\Infrastructure\SecurityBootloader::class,
            Bootloader\Infrastructure\CycleOrmBootloader::class,
            Bootloader\Infrastructure\TemporalBootloader::class,

            // Fast code prototyping
            PrototypeBootloader::class,

            // Application
            Bootloader\AppBootloader::class,
        ];
    }
}
