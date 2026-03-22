<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds suspension_reason column to users table.
 * Required by AdminService::suspendUser() / activateUser() (FR-ADM-003).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('users') && ! Schema::hasColumn('users', 'suspension_reason')) {
            Schema::table('users', function (Blueprint $table) {
                $table->text('suspension_reason')->nullable()->after('status');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'suspension_reason')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('suspension_reason');
            });
        }
    }
};
