<?php

declare(strict_types=1);

namespace Database\Migration;

use Cycle\Migrations\Migration;

class OrmDefault7dfecb5d76ee41917cd1dc86e90d232f extends Migration
{
    protected const DATABASE = 'default';

    public function up(): void
    {
        $this->table('vehicles')
        ->addColumn('created_at', 'datetime', ['nullable' => false, 'defaultValue' => null, 'withTimezone' => false])
        ->addColumn('uuid', 'uuid', ['nullable' => false, 'defaultValue' => null])
        ->addColumn('license_plate', 'string', ['nullable' => false, 'defaultValue' => null, 'size' => 255])
        ->addColumn('model', 'string', ['nullable' => false, 'defaultValue' => null, 'size' => 255])
        ->addColumn('vehicle_class', 'string', ['nullable' => false, 'defaultValue' => null, 'size' => 255])
        ->setPrimaryKeys(['uuid'])
        ->create();
    }

    public function down(): void
    {
        $this->table('vehicles')->drop();
    }
}
