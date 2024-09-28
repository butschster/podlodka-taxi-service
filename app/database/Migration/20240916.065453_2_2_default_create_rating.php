<?php

declare(strict_types=1);

namespace Database\Migration;

use Cycle\Migrations\Migration;

class OrmDefaultB4ec97f8ad7d3a4eca337a492e8b0f44 extends Migration
{
    protected const DATABASE = 'default';

    public function up(): void
    {
        $this->table('rating')
        ->addColumn('created_at', 'datetime', ['nullable' => false, 'defaultValue' => null, 'withTimezone' => false])
        ->addColumn('uuid', 'uuid', ['nullable' => false, 'defaultValue' => null])
        ->addColumn('trip_uuid', 'uuid', ['nullable' => false, 'defaultValue' => null])
        ->addColumn('recipient_uuid', 'uuid', ['nullable' => false, 'defaultValue' => null])
        ->addColumn('rating', 'integer', ['nullable' => false, 'defaultValue' => null])
        ->addColumn('comment', 'string', ['nullable' => true, 'defaultValue' => null, 'size' => 255])
        ->addIndex(['recipient_uuid'], ['name' => 'rating_index_recipient_uuid_66e7d63d1166a', 'unique' => false])
        ->addForeignKey(['recipient_uuid'], 'drivers', ['id'], [
            'name' => 'rating_foreign_recipient_uuid_66e7d63d11687',
            'delete' => 'CASCADE',
            'update' => 'CASCADE',
            'indexCreate' => true,
        ])
        ->setPrimaryKeys(['uuid'])
        ->create();
    }

    public function down(): void
    {
        $this->table('rating')->drop();
    }
}
