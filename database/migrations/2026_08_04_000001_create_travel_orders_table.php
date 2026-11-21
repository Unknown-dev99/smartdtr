<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('travel_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('staff_user_id')->constrained('staff_users')->cascadeOnDelete();
            $table->foreignId('personnel_id')->nullable()->constrained('personnel')->nullOnDelete();
            $table->string('position_designation');
            $table->string('permanent_station')->default('Isabela National High School');
            $table->text('purpose');
            $table->string('host_of_activity');
            $table->date('date_from');
            $table->date('date_to');
            $table->string('destination');
            $table->string('fund_source');
            $table->string('travel_type')->default('within'); // within | outside
            $table->string('attachment_path')->nullable();
            $table->string('status')->default('pending'); // pending|approved|rejected
            $table->foreignId('reviewed_by')->nullable()->constrained('admin_users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->string('admin_notes', 500)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('travel_orders');
    }
};
