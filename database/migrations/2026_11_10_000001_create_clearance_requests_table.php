<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clearance_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('personnel_id')->constrained('personnel')->cascadeOnDelete();
            $table->foreignId('staff_user_id')->nullable()->constrained('staff_users')->nullOnDelete();
            $table->unsignedSmallInteger('year');
            $table->date('request_date');
            $table->string('last_name');
            $table->string('first_name');
            $table->string('middle_initial', 10)->nullable();
            $table->string('home_address');
            $table->string('contact_no', 40);
            $table->string('email');
            $table->string('present_position');
            $table->string('agency_name');
            $table->string('agency_address');
            $table->string('purpose', 20);
            $table->string('purpose_other')->nullable();
            $table->timestamps();

            $table->unique(['personnel_id', 'year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clearance_requests');
    }
};
