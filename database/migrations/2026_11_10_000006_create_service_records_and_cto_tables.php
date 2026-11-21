<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('personnel_id')->unique()->constrained('personnel')->cascadeOnDelete();
            $table->foreignId('staff_user_id')->nullable()->constrained('staff_users')->nullOnDelete();
            $table->date('birth_date')->nullable();
            $table->string('birth_place')->nullable();
            $table->json('entries');
            $table->date('prepared_on');
            $table->timestamps();
        });

        Schema::create('compensatory_time_offs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('personnel_id')->unique()->constrained('personnel')->cascadeOnDelete();
            $table->foreignId('staff_user_id')->nullable()->constrained('staff_users')->nullOnDelete();
            $table->string('balance_hours')->nullable();
            $table->json('entries');
            $table->date('prepared_on');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('compensatory_time_offs');
        Schema::dropIfExists('service_records');
    }
};
