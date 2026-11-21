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

        DB::statement("ALTER TABLE attendance MODIFY COLUMN status ENUM(
            'Present',
            'Late',
            'Absent',
            'On Leave',
            'Official Travel',
            'Official Business',
            'Off-duty',
            'Unknown'
        ) NOT NULL DEFAULT 'Unknown'");

        DB::statement("ALTER TABLE personnel_daily_status MODIFY COLUMN status ENUM(
            'Present',
            'Late',
            'Absent',
            'On Leave',
            'Official Travel',
            'Official Business',
            'Off-duty'
        ) NOT NULL");
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return;
        }

        DB::table('attendance')->where('status', 'Official Business')->update(['status' => 'Official Travel']);
        DB::table('personnel_daily_status')->where('status', 'Official Business')->update(['status' => 'Official Travel']);

        DB::statement("ALTER TABLE attendance MODIFY COLUMN status ENUM(
            'Present',
            'Late',
            'Absent',
            'On Leave',
            'Official Travel',
            'Off-duty',
            'Unknown'
        ) NOT NULL DEFAULT 'Unknown'");

        DB::statement("ALTER TABLE personnel_daily_status MODIFY COLUMN status ENUM(
            'Present',
            'Late',
            'Absent',
            'On Leave',
            'Official Travel',
            'Off-duty'
        ) NOT NULL");
    }
};
