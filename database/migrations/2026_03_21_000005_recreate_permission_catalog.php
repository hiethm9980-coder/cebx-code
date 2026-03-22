<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Recreates the permission_catalog table that was dropped by
 * 2026_03_06_000222_phase2b2_cutover_legacy_authorization_storage.
 *
 * The Phase-2B2 cutover migration drops permission_catalog as part of
 * consolidating legacy auth storage, but:
 *   - PermissionCatalog model is still actively used by OrganizationService
 *   - KycComplianceTest / OrganizationTest write to it directly
 *
 * This migration runs AFTER the cutover drop and ensures the table exists.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('permission_catalog')) {
            Schema::create('permission_catalog', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('key', 100)->unique();
                $table->string('name', 200);
                $table->text('description')->nullable();
                $table->string('module', 50);
                $table->enum('category', ['operational', 'financial', 'admin'])->default('operational');
                $table->boolean('is_active')->default(true);
                $table->integer('sort_order')->default(0);
                $table->timestamps();

                $table->index(['module', 'category']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('permission_catalog');
    }
};
