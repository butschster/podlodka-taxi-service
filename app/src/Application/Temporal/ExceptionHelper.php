<?php

declare(strict_types=1);

namespace App\Application\Temporal;

use Taxi\Exception\AssignDriverProblemException;
use Taxi\Exception\DriverUnavailableException;
use Taxi\Exception\InvalidRequestStatusException;
use Taxi\Exception\RateLimitExceededException;
use Taxi\Exception\TaxiRequestCancelledException;
use Taxi\Exception\TaxiServiceException;
use Temporal\Exception\Failure\ActivityFailure;
use Temporal\Exception\Failure\ApplicationFailure;
use Temporal\Exception\Failure\TemporalFailure;

/**
 * Helper class to work with Temporal exceptions.
 * @internal
 * @template T of \Throwable
 */
final class ExceptionHelper
{
    /**
     * Check if the exception or any of its previous exceptions are of the specified type.
     * @param class-string<T> ...$exception
     */
    public static function isThrown(\Throwable $e, string ...$exception): bool
    {
        if (!$e instanceof TemporalFailure) {
            return false;
        }

        while ($e = $e->getPrevious()) {
            if (\method_exists($e, 'getType') && \in_array($e->getType(), $exception, true)) {
                return true;
            }
        }

        return false;
    }

    public static function isAssignDriverProblemException(\Throwable $e): bool
    {
        return self::isThrown($e, AssignDriverProblemException::class);
    }

    public static function isTaxiServiceException(\Throwable $e): bool
    {
        return self::isThrown($e, TaxiServiceException::class);
    }

    public static function isRateLimitExceeded(\Throwable $e): bool
    {
        return self::isThrown($e, RateLimitExceededException::class);
    }

    public static function isDriverUnavailable(\Throwable $e): bool
    {
        return self::isThrown($e, DriverUnavailableException::class);
    }

    public static function isInvalidRequestStatus(\Throwable $e): bool
    {
        return self::isThrown($e, InvalidRequestStatusException::class);
    }

    public static function isActivityFailure(\Throwable $e): bool
    {
        return $e instanceof ActivityFailure;
    }

    public static function getActivityFailureDetails(ActivityFailure $e): ?array
    {
        $applicationFailure = self::findException($e, ApplicationFailure::class);
        return $applicationFailure ? $applicationFailure->getDetails() : null;
    }

    /**
     * @param class-string<T> $exceptionClass
     * @return T|null
     */
    public static function findException(\Throwable $e, string $exceptionClass): ?\Throwable
    {
        if (!$e instanceof TemporalFailure) {
            return null;
        }

        $previous = $e->getPrevious();
        while ($previous !== null) {
            if ($previous instanceof $exceptionClass) {
                return $previous;
            }
            $previous = $previous->getPrevious();
        }

        return null;
    }

    /**
     * @param class-string<T> ...$retryableExceptions
     */
    public static function isRetryable(\Throwable $e, string ...$retryableExceptions): bool
    {
        return self::isThrown($e, ...$retryableExceptions);
    }

    /**
     * @throws T
     */
    public static function rethrowIfNotRetryable(\Throwable $e, string ...$retryableExceptions): never
    {
        if (!self::isRetryable($e, ...$retryableExceptions)) {
            throw $e;
        }

        throw new \RuntimeException('Exception is retryable, but rethrowIfNotRetryable was called', 0, $e);
    }

    /**
     * @param class-string<T> $exceptionClass
     * @return T|null
     */
    public static function extractFromTemporalFailure(TemporalFailure $failure, string $exceptionClass)
    {
        return self::findException($failure, $exceptionClass);
    }

    public static function categorizeException(\Throwable $e): ExceptionCategory
    {
        if (self::isRateLimitExceeded($e)) {
            return ExceptionCategory::RateLimit;
        }

        if (self::isDriverUnavailable($e)) {
            return ExceptionCategory::ResourceUnavailable;
        }

        if (self::isInvalidRequestStatus($e)) {
            return ExceptionCategory::InvalidState;
        }

        if (self::isActivityFailure($e)) {
            return ExceptionCategory::ActivityFailure;
        }

        if ($e instanceof TemporalFailure) {
            return ExceptionCategory::TemporalFailure;
        }

        return ExceptionCategory::Unknown;
    }

    /**
     * Determine if an exception is transient and can be retried
     */
    public static function isTransient(\Throwable $e): bool
    {
        return self::categorizeException($e)->isTransient();
    }

    /**
     * Get a user-friendly message based on the exception category
     */
    public static function getUserFriendlyMessage(\Throwable $e): string
    {
        return match (self::categorizeException($e)) {
            ExceptionCategory::RateLimit => 'The system is currently busy. Please try again later.',
            ExceptionCategory::ResourceUnavailable => 'The requested resource is temporarily unavailable. Please try again shortly.',
            ExceptionCategory::InvalidState => 'The operation cannot be completed due to an invalid state. Please check your request and try again.',
            ExceptionCategory::ActivityFailure => 'An error occurred while processing your request. Our team has been notified.',
            ExceptionCategory::TemporalFailure => 'A system error occurred. Please try again later.',
            default => 'An unexpected error occurred. Please contact support if the problem persists.',
        };
    }

    public static function shouldBeCompensated(\Throwable $e): bool
    {
        return $e instanceof TaxiRequestCancelledException || self::isDriverUnavailable($e);
    }
}
