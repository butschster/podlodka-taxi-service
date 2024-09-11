<?php

declare(strict_types=1);

namespace Taxi;

enum TaxiRequestStatus
{
    case Pending;
    case Accepted;
    case InProgress;
    case Completed;
    case Cancelled;
}
