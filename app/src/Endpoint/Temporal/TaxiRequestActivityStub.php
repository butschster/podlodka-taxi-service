<?php

declare(strict_types=1);

namespace App\Endpoint\Temporal;

use App\Endpoint\Temporal\Activity\TaxiRequestActivity;
use App\Endpoint\Temporal\Workflow\TaskQueue;
use Carbon\CarbonInterval;
use Temporal\Activity\ActivityOptions;
use Temporal\Common\RetryOptions;
use Temporal\Internal\Workflow\ActivityProxy;
use Temporal\Workflow;

final class TaxiRequestActivityStub
{
    public static function create(int $attempts = 2, int $timeout = 60): ActivityProxy|TaxiRequestActivity
    {
        return Workflow::newActivityStub(
            TaxiRequestActivity::class,
            ActivityOptions::new()
                ->withStartToCloseTimeout(CarbonInterval::seconds($timeout))
                ->withTaskQueue(TaskQueue::TAXI_SERVICE)
                ->withRetryOptions(
                    RetryOptions::new()
                        ->withMaximumAttempts($attempts)
                        ->withBackoffCoefficient(1.5),
                ),
        );
    }
}
