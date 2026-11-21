<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('personnel', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('role');
            $table->string('employee_number')->nullable();
            $table->string('official_hours_regular')->nullable();
            $table->string('official_hours_saturday')->nullable();
            $table->string('face_dataset_dir')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('personnel');
    }
};
