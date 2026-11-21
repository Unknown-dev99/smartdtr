<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return;
        }

        foreach (['leave_requests_data', 'leave_requests'] as $table) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'status')) {
                continue;
            }

            DB::statement("ALTER TABLE `{$table}` MODIFY `status` VARCHAR(32) NOT NULL DEFAULT 'pending'");
        }
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return;
        }

        foreach (['leave_requests_data', 'leave_requests'] as $table) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'status')) {
                continue;
            }

            DB::statement("ALTER TABLE `{$table}` MODIFY `status` ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending'");
        }
    }
};
