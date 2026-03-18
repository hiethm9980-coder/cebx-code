<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('companies')) {
            return;
        }
        Schema::table('companies', function (Blueprint $table) {
            if (!Schema::hasColumn('companies', 'code')) {
                $table->string('code', 50)->nullable()->after('name');
            }
            if (!Schema::hasColumn('companies', 'type')) {
                $table->string('type', 30)->nullable()->after('code');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('companies')) {
            return;
        }
        Schema::table('companies', function (Blueprint $table) {
            if (Schema::hasColumn('companies', 'code')) {
                $table->dropColumn('code');
            }
            if (Schema::hasColumn('companies', 'type')) {
                $table->dropColumn('type');
            }
        });
    }
};
