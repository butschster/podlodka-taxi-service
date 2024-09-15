<?php

declare(strict_types=1);

namespace Taxi;

enum TaxiRequestStatus: string
{
    case Pending = 'pending'; // The request is waiting for a driver to accept it
    case Accepted = 'accepted'; // The request has been accepted by a driver
    case InProgress = 'in-progress'; // The ride is in progress
    case Completed = 'completed'; // The ride has been completed
    case Cancelled = 'cancelled'; // The ride has been cancelled by the user
}
