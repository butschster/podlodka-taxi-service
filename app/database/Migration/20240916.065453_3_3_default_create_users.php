<?php

declare(strict_types=1);

namespace Database\Migration;

use Cycle\Migrations\Migration;

class OrmDefault65f7b851d72dbedccfdab2b1e1fcaf46 extends Migration
{
    protected const DATABASE = 'default';

    public function up(): void
    {
        $this->table('users')
        ->addColumn('created_at', 'datetime', ['nullable' => false, 'defaultValue' => null, 'withTimezone' => false])
        ->addColumn('id', 'uuid', ['nullable' => false, 'defaultValue' => null])
        ->addColumn('name', 'string', ['nullable' => false, 'defaultValue' => null, 'size' => 255])
        ->addColumn('phone', 'string', ['nullable' => false, 'defaultValue' => null, 'size' => 255])
        ->setPrimaryKeys(['id'])
        ->create();
    }

    public function down(): void
    {
        $this->table('users')->drop();
    }
}
