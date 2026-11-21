<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff_users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('personnel_id')->nullable()->constrained('personnel')->nullOnDelete();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('username')->nullable()->unique();
            $table->string('password');
            $table->enum('role', ['employee', 'teacher', 'non_teaching', 'cleaning_staff'])->default('employee');
            $table->string('employee_number')->nullable();
            $table->string('department')->nullable();
            $table->enum('status', ['pending', 'active', 'rejected', 'suspended'])->default('pending');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_users');
    }
};
