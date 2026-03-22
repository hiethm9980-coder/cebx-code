<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }

        // Check current ENUM values — only alter if 'disabled' is missing
        $column = DB::selectOne(
            "SELECT COLUMN_TYPE FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = 'users'
               AND COLUMN_NAME = 'status'"
        );

        if ($column === null) {
            return;
        }

        $type = (string) ($column->COLUMN_TYPE ?? $column->column_type ?? '');

        if (str_contains($type, "'disabled'")) {
            return; // already present
        }

        DB::statement(
            "ALTER TABLE users
             MODIFY COLUMN status
             ENUM('active','inactive','suspended','disabled')
             NOT NULL DEFAULT 'active'"
        );
    }

    public function down(): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }

        // Remove 'disabled' — move any disabled users to 'inactive' first to avoid data loss
        DB::table('users')->where('status', 'disabled')->update(['status' => 'inactive']);

        DB::statement(
            "ALTER TABLE users
             MODIFY COLUMN status
             ENUM('active','inactive','suspended')
             NOT NULL DEFAULT 'active'"
        );
    }
};
