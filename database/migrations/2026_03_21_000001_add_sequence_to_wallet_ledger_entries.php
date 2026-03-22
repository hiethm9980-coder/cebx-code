<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * FIX: Add missing columns to `wallet_ledger_entries` table.
 *
 * Migration 2026_02_12_000011 creates wallet_ledger_entries WITHOUT the columns
 * used by BillingWalletService (sequence, correlation_id, transaction_type,
 * direction, notes, reversal_of, created_by).
 * Migration 2026_02_12_000024 creates the complete version but its
 * Schema::hasTable guard skips creation when 000011 already ran.
 * This migration adds the missing columns so BillingWalletService works correctly.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('wallet_ledger_entries')) {
            return;
        }

        Schema::table('wallet_ledger_entries', function (Blueprint $table) {
            if (!Schema::hasColumn('wallet_ledger_entries', 'sequence')) {
                $table->unsignedBigInteger('sequence')->nullable()->after('wallet_id');
            }

            if (!Schema::hasColumn('wallet_ledger_entries', 'correlation_id')) {
                $table->string('correlation_id', 200)->nullable()->after('sequence');
            }

            if (!Schema::hasColumn('wallet_ledger_entries', 'transaction_type')) {
                $table->string('transaction_type', 50)->nullable()->after('correlation_id');
            }

            if (!Schema::hasColumn('wallet_ledger_entries', 'direction')) {
                $table->string('direction', 10)->nullable()->after('transaction_type');
            }

            if (!Schema::hasColumn('wallet_ledger_entries', 'notes')) {
                $table->text('notes')->nullable();
            }

            if (!Schema::hasColumn('wallet_ledger_entries', 'reversal_of')) {
                $table->string('reversal_of', 100)->nullable();
            }

            if (!Schema::hasColumn('wallet_ledger_entries', 'created_by')) {
                $table->string('created_by', 100)->nullable();
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('wallet_ledger_entries')) {
            return;
        }

        Schema::table('wallet_ledger_entries', function (Blueprint $table) {
            $columns = [
                'sequence', 'correlation_id', 'transaction_type',
                'direction', 'notes', 'reversal_of', 'created_by',
            ];
            foreach ($columns as $col) {
                if (Schema::hasColumn('wallet_ledger_entries', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
