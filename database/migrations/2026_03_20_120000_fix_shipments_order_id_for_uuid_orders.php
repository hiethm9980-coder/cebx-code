<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Legacy core migration used bigint for shipments.order_id and orders.shipment_id while
 * orders.id / shipments.id were later migrated to UUID strings — inserts then fail with
 * "Incorrect integer value" when confirming ship from the web UI.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return;
        }

        $this->dropMysqlForeignKeysForColumn('shipments', 'order_id');
        if (Schema::hasColumn('shipments', 'order_id')) {
            DB::statement('ALTER TABLE `shipments` MODIFY `order_id` CHAR(36) NULL');
        }

        $this->dropMysqlForeignKeysForColumn('orders', 'shipment_id');
        if (Schema::hasColumn('orders', 'shipment_id')) {
            DB::statement('ALTER TABLE `orders` MODIFY `shipment_id` CHAR(36) NULL');
        }
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return;
        }

        $this->dropMysqlForeignKeysForColumn('shipments', 'order_id');
        if (Schema::hasColumn('shipments', 'order_id')) {
            DB::statement('ALTER TABLE `shipments` MODIFY `order_id` BIGINT UNSIGNED NULL');
        }

        $this->dropMysqlForeignKeysForColumn('orders', 'shipment_id');
        if (Schema::hasColumn('orders', 'shipment_id')) {
            DB::statement('ALTER TABLE `orders` MODIFY `shipment_id` BIGINT UNSIGNED NULL');
        }
    }

    private function dropMysqlForeignKeysForColumn(string $table, string $column): void
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
            return;
        }

        $db = Schema::getConnection()->getDatabaseName();
        $rows = DB::select(
            'SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE
             WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ? AND REFERENCED_TABLE_NAME IS NOT NULL',
            [$db, $table, $column]
        );

        foreach ($rows as $row) {
            $name = $row->CONSTRAINT_NAME ?? null;
            if (is_string($name) && $name !== '') {
                DB::statement('ALTER TABLE `'.$table.'` DROP FOREIGN KEY `'.$name.'`');
            }
        }
    }
};
