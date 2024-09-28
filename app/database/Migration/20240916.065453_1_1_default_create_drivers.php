<?php

declare(strict_types=1);

namespace Database\Migration;

use Cycle\Migrations\Migration;

class OrmDefault930df709cae867444539ac0f64bde6bd extends Migration
{
    protected const DATABASE = 'default';

    public function up(): void
    {
        $this->table('drivers')
        ->addColumn('is_available', 'boolean', ['nullable' => false, 'defaultValue' => null])
        ->addColumn('created_at', 'datetime', ['nullable' => false, 'defaultValue' => null, 'withTimezone' => false])
        ->addColumn('current_location', 'jsonb', ['nullable' => true, 'defaultValue' => null])
        ->addColumn('id', 'uuid', ['nullable' => false, 'defaultValue' => null])
        ->addColumn('name', 'string', ['nullable' => false, 'defaultValue' => null, 'size' => 255])
        ->addColumn('phone', 'string', ['nullable' => false, 'defaultValue' => null, 'size' => 255])
        ->addColumn('vehicleUuid', 'uuid', ['nullable' => false, 'defaultValue' => null])
        ->addIndex(['vehicleUuid'], ['name' => 'drivers_index_vehicleuuid_66e7d63d117aa', 'unique' => false])
        ->addForeignKey(['vehicleUuid'], 'vehicles', ['uuid'], [
            'name' => 'drivers_foreign_vehicleuuid_66e7d63d117bc',
            'delete' => 'CASCADE',
            'update' => 'CASCADE',
            'indexCreate' => true,
        ])
        ->setPrimaryKeys(['id'])
        ->create();
    }

    public function down(): void
    {
        $this->table('drivers')->drop();
    }
}
