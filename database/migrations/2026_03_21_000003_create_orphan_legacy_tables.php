<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Creates tables that only existed in the now-no-op 0001_01_01_000001/000002 migrations.
 * These tables are not duplicated in any 2026 migration, so they must be created here
 * on fresh installs (including test environments using RefreshDatabase).
 *
 * All FK references to accounts.id use uuid (not bigint) to match the real schema.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── ticket_replies (from 0001_01_01_000001) ──────────────────────────
        if (! Schema::hasTable('ticket_replies')) {
            Schema::create('ticket_replies', function (Blueprint $table) {
                $table->uuid('id')->primary()->default('');
                $table->uuid('support_ticket_id')->index();
                $table->uuid('user_id')->nullable()->index();
                $table->text('body');
                $table->boolean('is_agent')->default(false);
                $table->timestamps();
            });
        }

        // ── schedules (from 0001_01_01_000002) ───────────────────────────────
        if (! Schema::hasTable('schedules')) {
            Schema::create('schedules', function (Blueprint $table) {
                $table->uuid('id')->primary()->default('');
                $table->string('voyage_number')->unique();
                $table->uuid('vessel_id')->nullable()->index();
                $table->string('origin_port');
                $table->string('destination_port');
                $table->timestamp('departure_date')->nullable();
                $table->timestamp('arrival_date')->nullable();
                $table->string('status')->default('scheduled');
                $table->timestamps();
            });
        }

        // ── kyc_requests (from 0001_01_01_000002) ────────────────────────────
        if (! Schema::hasTable('kyc_requests')) {
            Schema::create('kyc_requests', function (Blueprint $table) {
                $table->uuid('id')->primary()->default('');
                $table->uuid('account_id')->index();
                $table->enum('type', ['individual', 'company'])->default('company');
                $table->string('status')->default('pending');
                $table->integer('documents_count')->default(0);
                $table->uuid('reviewer_id')->nullable()->index();
                $table->timestamps();

                $table->foreign('account_id')
                      ->references('id')->on('accounts')
                      ->cascadeOnDelete();
            });
        }

        // ── dg_classifications (from 0001_01_01_000002) ───────────────────────
        if (! Schema::hasTable('dg_classifications')) {
            Schema::create('dg_classifications', function (Blueprint $table) {
                $table->uuid('id')->primary()->default('');
                $table->integer('class_number');
                $table->string('division')->nullable();
                $table->string('description');
                $table->string('un_number')->nullable();
                $table->string('packing_group')->nullable();
                $table->text('restrictions')->nullable();
                $table->boolean('is_allowed')->default(true);
                $table->timestamps();
            });
        }

        // ── risk_rules (from 0001_01_01_000002) ──────────────────────────────
        if (! Schema::hasTable('risk_rules')) {
            Schema::create('risk_rules', function (Blueprint $table) {
                $table->uuid('id')->primary()->default('');
                $table->string('name');
                $table->text('condition_description')->nullable();
                $table->enum('risk_level', ['low', 'medium', 'high'])->default('medium');
                $table->string('action_description')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        // ── risk_alerts (from 0001_01_01_000002) ─────────────────────────────
        if (! Schema::hasTable('risk_alerts')) {
            Schema::create('risk_alerts', function (Blueprint $table) {
                $table->uuid('id')->primary()->default('');
                $table->uuid('risk_rule_id')->nullable()->index();
                $table->string('title');
                $table->text('description')->nullable();
                $table->enum('level', ['low', 'medium', 'high'])->default('medium');
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('risk_alerts');
        Schema::dropIfExists('risk_rules');
        Schema::dropIfExists('dg_classifications');
        Schema::dropIfExists('kyc_requests');
        Schema::dropIfExists('schedules');
        Schema::dropIfExists('ticket_replies');
    }
};
