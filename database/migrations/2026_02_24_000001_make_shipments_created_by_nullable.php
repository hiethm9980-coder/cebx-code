<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * جعل created_by قابلاً للـ null عند عدم توفر مستخدم صالح (مثلاً auth()->id() = 0).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            $table->dropForeign(['created_by']);
        });
        Schema::table('shipments', function (Blueprint $table) {
            $table->uuid('created_by')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            $table->uuid('created_by')->nullable(false)->change();
        });
        Schema::table('shipments', function (Blueprint $table) {
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
        });
    }
};
