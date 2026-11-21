<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('position_description_forms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('personnel_id')->unique()->constrained('personnel')->cascadeOnDelete();
            $table->foreignId('staff_user_id')->nullable()->constrained('staff_users')->nullOnDelete();
            $table->string('employee_name');
            $table->date('employee_date');
            $table->json('details');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('position_description_forms');
    }
};
