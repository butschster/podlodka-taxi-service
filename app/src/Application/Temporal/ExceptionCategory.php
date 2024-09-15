<?php

declare(strict_types=1);

namespace App\Application\Temporal;

enum ExceptionCategory: string
{
    case RateLimit = 'rateLimit';
    case ResourceUnavailable = 'resourceUnavailable';
    case InvalidState = 'invalidState';
    case ActivityFailure = 'activityFailure';
    case TemporalFailure = 'temporalFailure';
    case Unknown = 'unknown';

    public function isTransient(): bool
    {
        return match ($this) {
            self::RateLimit, self::ResourceUnavailable => true,
            default => false,
        };
    }
}
