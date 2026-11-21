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

        DB::statement("ALTER TABLE staff_users MODIFY COLUMN role ENUM('teacher', 'non_teaching', 'cleaning_staff') NOT NULL");
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        DB::statement("ALTER TABLE staff_users MODIFY COLUMN role ENUM('teacher', 'non_teaching') NOT NULL");
    }
};
