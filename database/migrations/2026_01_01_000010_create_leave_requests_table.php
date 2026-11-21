<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leave_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('staff_user_id')->constrained('staff_users')->cascadeOnDelete();
            $table->foreignId('personnel_id')->nullable()->constrained('personnel')->nullOnDelete();
            $table->enum('leave_type', ['Leave', 'Travel', 'Sick', 'Emergency', 'Other']);
            $table->date('date_from');
            $table->date('date_to');
            $table->text('reason')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->foreignId('reviewed_by')->nullable()->constrained('admin_users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('admin_notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_requests');
    }
};
