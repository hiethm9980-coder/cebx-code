<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // No-op: superseded by 2026_02_12_000001 (UUID accounts) and 2026_02_12_000002 (UUID users).
        // The legacy bigint schema here conflicts with UUID foreign keys in all 2026 migrations.
        return;
    }

    public function down(): void
    {
        // No-op: nothing was created here.
    }

    /**
     * @internal kept for reference only; never executed.
     */
    private function _legacyUp(): void
    {
        if (Schema::hasTable('accounts')) {
            return;
        }

        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('name_en')->nullable();
            $table->enum('type', ['individual', 'organization'])->default('individual');
            $table->string('email')->nullable()->unique();
            $table->string('phone')->nullable();
            $table->string('cr_number')->nullable()->comment('السجل التجاري');
            $table->string('vat_number')->nullable()->comment('الرقم الضريبي');
            $table->enum('status', ['active', 'pending', 'suspended'])->default('pending');
            $table->enum('kyc_status', ['not_submitted', 'pending', 'verified', 'rejected'])->default('not_submitted');
            $table->timestamps();
        });

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('phone')->nullable();
            $table->string('password');
            $table->string('job_title')->nullable();
            $table->string('role_name')->default('مشغّل');
            $table->enum('role', ['admin', 'manager', 'supervisor', 'operator', 'viewer'])->default('operator');
            $table->boolean('is_active')->default(true);
            $table->boolean('is_super_admin')->default(false);
            $table->unsignedBigInteger('branch_id')->nullable()->index();
            $table->timestamp('last_login_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }
};
