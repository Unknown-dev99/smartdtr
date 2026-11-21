<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leave_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('personnel_id')->constrained('personnel')->cascadeOnDelete();
            $table->date('date');
            $table->enum('leave_type', ['Leave', 'Travel']);
            $table->string('reason')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('admin_users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['personnel_id', 'date', 'leave_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_records');
    }
};
