<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('locator_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('staff_user_id')->constrained('staff_users')->cascadeOnDelete();
            $table->foreignId('personnel_id')->nullable()->constrained('personnel')->nullOnDelete();
            $table->date('date');
            $table->time('time_out');
            $table->time('time_in_expected');
            $table->string('destination');
            $table->string('purpose');
            $table->string('nature')->default('Official Business'); // Official Business | Official Time
            $table->string('accompanied_by')->nullable();
            $table->string('contact_number')->nullable();
            $table->text('remarks')->nullable();
            $table->string('attachment_path')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected', 'cancelled'])->default('pending');
            $table->foreignId('reviewed_by')->nullable()->constrained('admin_users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('admin_notes')->nullable();
            $table->time('actual_time_out')->nullable();
            $table->time('actual_time_in')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('locator_requests');
    }
};
