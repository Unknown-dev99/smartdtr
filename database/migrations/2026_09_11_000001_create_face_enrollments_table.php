<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('face_enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('staff_user_id')->constrained('staff_users')->cascadeOnDelete();
            $table->foreignId('personnel_id')->nullable()->constrained('personnel')->nullOnDelete();
            $table->string('status', 20)->default('pending');
            $table->string('reject_reason')->nullable();
            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('face_enrollments');
    }
};
