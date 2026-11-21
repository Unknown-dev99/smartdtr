<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('staff_users', function (Blueprint $table) {
            $table->unique('phone', 'staff_users_phone_unique');
            $table->unique('employee_number', 'staff_users_employee_number_unique');
        });
    }

    public function down(): void
    {
        Schema::table('staff_users', function (Blueprint $table) {
            $table->dropUnique('staff_users_phone_unique');
            $table->dropUnique('staff_users_employee_number_unique');
        });
    }
};
