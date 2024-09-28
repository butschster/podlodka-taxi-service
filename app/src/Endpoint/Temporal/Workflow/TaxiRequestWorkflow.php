<?php

declare(strict_types=1);

namespace App\Endpoint\Temporal\Workflow;

final class TaxiRequestWorkflow
{
    public function createRequest()
    {
        // 1. First, we need to create a taxi request

        // 2. Then we need to block money on user account for the ride
        //   - If user doesn't have enough money, we can notify the user and cancel the request

        // If something goes wrong, we need to refund the money

        // 3. Notify drivers about the new request
        // 4. Wait max for 5 minutes for driver to accept the request
        // 5. If driver is accepted, first validate the driver (class, rating, etc)
        //   - If the driver is not matched, wait for another driver
        // 6. Assign the driver to the request
        // 7. Notify the user that the driver accepted the request

        // 8. If the driver accepted the request, we can start the trip
        // 9. Charge user for the trip
        // 10. Notify user about the trip finish and ask to rate each other
    }
}
