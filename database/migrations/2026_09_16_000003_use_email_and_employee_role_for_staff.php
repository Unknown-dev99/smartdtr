<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        DB::statement('ALTER TABLE staff_users MODIFY COLUMN username VARCHAR(255) NULL');
        DB::statement("ALTER TABLE staff_users MODIFY COLUMN role ENUM('employee', 'teacher', 'non_teaching', 'cleaning_staff') NOT NULL DEFAULT 'employee'");
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        DB::table('staff_users')->where('role', 'employee')->update(['role' => 'non_teaching']);
        DB::statement("ALTER TABLE staff_users MODIFY COLUMN role ENUM('teacher', 'non_teaching', 'cleaning_staff') NOT NULL");
    }
};
