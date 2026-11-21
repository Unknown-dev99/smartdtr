<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance', function (Blueprint $table) {
            $table->id();
            $table->foreignId('personnel_id')->constrained('personnel')->cascadeOnDelete();
            $table->date('date');
            $table->time('time_in')->nullable();
            $table->time('time_out')->nullable();
            $table->enum('status', [
                'Present',
                'Late',
                'Absent',
                'On Leave',
                'Official Travel',
                'Official Business',
                'Off-duty',
                'Unknown',
            ])->default('Unknown');
            $table->timestamp('updated_at')->nullable();

            $table->unique(['personnel_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance');
    }
};
