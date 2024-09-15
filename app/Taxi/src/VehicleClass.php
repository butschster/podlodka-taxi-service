<?php

declare(strict_types=1);

namespace Taxi;

enum VehicleClass: string
{
    case Economy = 'economy';
    case Comfort = 'comfort';
    case Business = 'business';
}
