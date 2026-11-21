<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('personal_data_sheets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('personnel_id')->unique()->constrained('personnel')->cascadeOnDelete();
            $table->foreignId('staff_user_id')->nullable()->constrained('staff_users')->nullOnDelete();
            $table->date('date_accomplished')->nullable();
            $table->json('details');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('personal_data_sheets');
    }
};
