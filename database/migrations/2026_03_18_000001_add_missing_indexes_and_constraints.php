<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Index: shipments.carrier_tracking_number (used in public tracking lookup)
        if (Schema::hasTable('shipments') && Schema::hasColumn('shipments', 'carrier_tracking_number')) {
            Schema::table('shipments', function (Blueprint $table) {
                if (!$this->indexExists('shipments', 'shipments_carrier_tracking_number_index')) {
                    $table->index('carrier_tracking_number', 'shipments_carrier_tracking_number_index');
                }
            });
        }

        // Index: shipments.account_id + status (used in dashboard queries)
        if (Schema::hasTable('shipments') && Schema::hasColumn('shipments', 'account_id')) {
            Schema::table('shipments', function (Blueprint $table) {
                if (!$this->indexExists('shipments', 'shipments_account_status_index')) {
                    $table->index(['account_id', 'status'], 'shipments_account_status_index');
                }
            });
        }

        // Index: tracking_events.tracking_number
        if (Schema::hasTable('tracking_events') && Schema::hasColumn('tracking_events', 'tracking_number')) {
            Schema::table('tracking_events', function (Blueprint $table) {
                if (!$this->indexExists('tracking_events', 'tracking_events_tracking_number_index')) {
                    $table->index('tracking_number', 'tracking_events_tracking_number_index');
                }
            });
        }

        // Index: wallet_ledger_entries.wallet_id (used in balance computation)
        if (Schema::hasTable('wallet_ledger_entries') && Schema::hasColumn('wallet_ledger_entries', 'wallet_id')) {
            Schema::table('wallet_ledger_entries', function (Blueprint $table) {
                if (!$this->indexExists('wallet_ledger_entries', 'wle_wallet_id_index')) {
                    $table->index('wallet_id', 'wle_wallet_id_index');
                }
            });
        }

        // Index: payment_transactions.account_id + direction + status (balance sum query)
        if (Schema::hasTable('payment_transactions') && Schema::hasColumn('payment_transactions', 'account_id')) {
            Schema::table('payment_transactions', function (Blueprint $table) {
                if (!$this->indexExists('payment_transactions', 'pt_account_dir_status_index')) {
                    $table->index(['account_id', 'direction', 'status'], 'pt_account_dir_status_index');
                }
            });
        }

        // Index: audit_logs.account_id + created_at (audit log queries)
        if (Schema::hasTable('audit_logs') && Schema::hasColumn('audit_logs', 'account_id')) {
            Schema::table('audit_logs', function (Blueprint $table) {
                if (!$this->indexExists('audit_logs', 'audit_logs_account_created_index')) {
                    $table->index(['account_id', 'created_at'], 'audit_logs_account_created_index');
                }
            });
        }

        // Index: notifications.account_id + status (notification inbox query — column is 'status', not 'is_read')
        if (Schema::hasTable('notifications') && Schema::hasColumn('notifications', 'account_id') && Schema::hasColumn('notifications', 'status')) {
            Schema::table('notifications', function (Blueprint $table) {
                if (!$this->indexExists('notifications', 'notifications_account_status_index')) {
                    $table->index(['account_id', 'status'], 'notifications_account_status_index');
                }
            });
        }
    }

    public function down(): void
    {
        $drops = [
            'shipments'             => ['shipments_carrier_tracking_number_index', 'shipments_account_status_index'],
            'tracking_events'       => ['tracking_events_tracking_number_index'],
            'wallet_ledger_entries' => ['wle_wallet_id_index'],
            'payment_transactions'  => ['pt_account_dir_status_index'],
            'audit_logs'            => ['audit_logs_account_created_index'],
            'notifications'         => ['notifications_account_status_index'],
        ];

        foreach ($drops as $table => $indexes) {
            if (Schema::hasTable($table)) {
                Schema::table($table, function (Blueprint $t) use ($indexes) {
                    foreach ($indexes as $idx) {
                        try { $t->dropIndex($idx); } catch (\Throwable) {}
                    }
                });
            }
        }
    }

    private function indexExists(string $table, string $indexName): bool
    {
        try {
            $indexes = \Illuminate\Support\Facades\DB::select("SHOW INDEX FROM `{$table}` WHERE Key_name = ?", [$indexName]);
            return count($indexes) > 0;
        } catch (\Throwable) {
            return false;
        }
    }
};
