<?php

declare(strict_types=1);

namespace Database\Migration;

use Cycle\Migrations\Migration;

class OrmDefaultF212e57af1ed2617a4458425f8443702 extends Migration
{
    protected const DATABASE = 'default';

    public function up(): void
    {
        $this->table('trips')
        ->addColumn('end_time', 'datetime', ['nullable' => true, 'defaultValue' => null, 'withTimezone' => false])
        ->addColumn('final_price', 'float', ['nullable' => true, 'defaultValue' => null])
        ->addColumn('user_rating_uuid', 'uuid', ['nullable' => true, 'defaultValue' => null])
        ->addColumn('driver_rating_uuid', 'uuid', ['nullable' => true, 'defaultValue' => null])
        ->addColumn('uuid', 'uuid', ['nullable' => false, 'defaultValue' => null])
        ->addColumn('taxi_request_uuid', 'uuid', ['nullable' => false, 'defaultValue' => null])
        ->addColumn('start_time', 'datetime', ['nullable' => false, 'defaultValue' => null, 'withTimezone' => false])
        ->addIndex(['taxi_request_uuid'], ['name' => 'trips_index_taxi_request_uuid_66e7d63d1174b', 'unique' => false])
        ->addForeignKey(['taxi_request_uuid'], 'taxi_requests', ['uuid'], [
            'name' => 'trips_foreign_taxi_request_uuid_66e7d63d1175f',
            'delete' => 'CASCADE',
            'update' => 'CASCADE',
            'indexCreate' => true,
        ])
        ->setPrimaryKeys(['uuid'])
        ->create();
    }

    public function down(): void
    {
        $this->table('trips')->drop();
    }
}
