<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * FR-IAM-008: Account Settings
 *
 * Adds dedicated columns for core settings alongside existing JSONB settings.
 * Dedicated columns: language, currency, timezone, country, phone, email (contact).
 * JSONB settings: extended/custom preferences.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            $table->string('language', 10)->default('ar')->after('settings');
            $table->string('currency', 3)->default('SAR')->after('language');
            $table->string('timezone', 50)->default('Asia/Riyadh')->after('currency');
            $table->string('country', 3)->default('SA')->after('timezone');
            $table->string('contact_phone', 20)->nullable()->after('country');
            $table->string('contact_email', 255)->nullable()->after('contact_phone');
            $table->string('address_line_1', 255)->nullable()->after('contact_email');
            $table->string('address_line_2', 255)->nullable()->after('address_line_1');
            $table->string('city', 100)->nullable()->after('address_line_2');
            $table->string('postal_code', 20)->nullable()->after('city');
            $table->string('date_format', 20)->default('Y-m-d')->after('postal_code');
            $table->string('weight_unit', 5)->default('kg')->after('date_format');
            $table->string('dimension_unit', 5)->default('cm')->after('weight_unit');
        });
    }

    public function down(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            $table->dropColumn([
                'language', 'currency', 'timezone', 'country',
                'contact_phone', 'contact_email',
                'address_line_1', 'address_line_2', 'city', 'postal_code',
                'date_format', 'weight_unit', 'dimension_unit',
            ]);
        });
    }
};
