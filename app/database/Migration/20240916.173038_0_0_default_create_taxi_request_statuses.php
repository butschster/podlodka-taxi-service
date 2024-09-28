<?php

declare(strict_types=1);

namespace Database\Migration;

use Cycle\Migrations\Migration;

class OrmDefault1759cd334b681de265daa5ad0d5dd2a9 extends Migration
{
    protected const DATABASE = 'default';

    public function up(): void
    {
        $this->table('taxi_request_statuses')
        ->addColumn('created_at', 'datetime', ['nullable' => false, 'defaultValue' => null, 'withTimezone' => false])
        ->addColumn('uuid', 'uuid', ['nullable' => false, 'defaultValue' => null])
        ->addColumn('taxi_request_uuid', 'uuid', ['nullable' => false, 'defaultValue' => null])
        ->addColumn('status', 'string', ['nullable' => false, 'defaultValue' => null, 'size' => 255])
        ->addColumn('reason', 'text', ['nullable' => true, 'defaultValue' => null])
        ->addIndex(['taxi_request_uuid'], ['name' => '149aeebaa4471c344e71171edc7a333e', 'unique' => false])
        ->addForeignKey(['taxi_request_uuid'], 'taxi_requests', ['uuid'], [
            'name' => '1c1b03b8d5c35bb320cc079c1652d7de',
            'delete' => 'CASCADE',
            'update' => 'CASCADE',
            'indexCreate' => true,
        ])
        ->setPrimaryKeys(['uuid'])
        ->create();
    }

    public function down(): void
    {
        $this->table('taxi_request_statuses')->drop();
    }
}
