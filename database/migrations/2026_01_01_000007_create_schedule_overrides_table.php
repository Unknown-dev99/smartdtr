<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('schedule_overrides', function (Blueprint $table) {
            $table->id();
            $table->foreignId('personnel_id')->constrained('personnel')->cascadeOnDelete();
            $table->date('date');
            $table->enum('override_type', ['Class', 'Off-duty']);
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->string('subject')->nullable();
            $table->timestamps();

            $table->unique(['personnel_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('schedule_overrides');
    }
};
