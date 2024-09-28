<?php

declare(strict_types=1);

namespace Database\Migration;

use Cycle\Migrations\Migration;

class OrmDefaultB4ec6c2e891a0062977855108eb92f02 extends Migration
{
    protected const DATABASE = 'default';

    public function up(): void
    {
        $this->table('taxi_requests')
        ->addColumn('created_at', 'datetime', ['nullable' => false, 'defaultValue' => null, 'withTimezone' => false])
        ->addColumn('finished_at', 'datetime', ['nullable' => true, 'defaultValue' => null, 'withTimezone' => false])
        ->addColumn('driver_uuid', 'uuid', ['nullable' => true, 'defaultValue' => null])
        ->addColumn('status', 'string', ['nullable' => false, 'defaultValue' => null, 'size' => 255])
        ->addColumn('uuid', 'uuid', ['nullable' => false, 'defaultValue' => null])
        ->addColumn('user_uuid', 'uuid', ['nullable' => false, 'defaultValue' => null])
        ->addColumn('current_location', 'jsonb', ['nullable' => false, 'defaultValue' => null])
        ->addColumn('destination_location', 'jsonb', ['nullable' => false, 'defaultValue' => null])
        ->addColumn('vehicle_class', 'string', ['nullable' => false, 'defaultValue' => null, 'size' => 255])
        ->addColumn('estimated_price', 'float', ['nullable' => false, 'defaultValue' => null])
        ->setPrimaryKeys(['uuid'])
        ->create();
    }

    public function down(): void
    {
        $this->table('taxi_requests')->drop();
    }
}
