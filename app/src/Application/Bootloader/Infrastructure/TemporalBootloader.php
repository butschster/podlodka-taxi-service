<?php

declare(strict_types=1);

namespace App\Application\Bootloader\Infrastructure;

use Spiral\Boot\Bootloader\Bootloader;
use Spiral\TemporalBridge\Bootloader as TemporalBridge;

final class TemporalBootloader extends Bootloader
{
    public function defineDependencies(): array
    {
        return [
            TemporalBridge\PrototypeBootloader::class,
            TemporalBridge\TemporalBridgeBootloader::class,
        ];
    }
}
