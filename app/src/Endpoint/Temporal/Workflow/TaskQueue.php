<?php

declare(strict_types=1);

namespace App\Endpoint\Temporal\Workflow;

final class TaskQueue
{
    public const string TAXI_SERVICE = 'taxi-service';
    public const string PAYMENT_SERVICE = 'payment-service';
    public const string NOTIFICATION_SERVICE = 'notification-service';
}
