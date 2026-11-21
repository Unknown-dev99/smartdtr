<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('work_experience_sheets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('personnel_id')->unique()->constrained('personnel')->cascadeOnDelete();
            $table->foreignId('staff_user_id')->nullable()->constrained('staff_users')->nullOnDelete();
            $table->date('sheet_date');
            $table->string('printed_name');
            $table->json('entries');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('work_experience_sheets');
    }
};
