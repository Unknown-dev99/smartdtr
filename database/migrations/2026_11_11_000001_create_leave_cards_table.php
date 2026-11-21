<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leave_cards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('personnel_id')->unique()->constrained('personnel')->cascadeOnDelete();
            $table->date('entrance_to_duty')->nullable();
            $table->string('civil_status')->nullable();
            $table->string('employment_status')->default('Permanent');
            $table->string('unit')->default('SDO Ilagan - Isabela NHS');
            $table->string('gsis_policy_no')->nullable();
            $table->string('tin')->nullable();
            $table->string('national_reference_no')->nullable();
            $table->string('prepared_by')->nullable();
            $table->string('prepared_title')->nullable();
            $table->string('approved_by')->nullable();
            $table->string('approved_title')->nullable();
            $table->string('noted_by')->nullable();
            $table->string('noted_title')->nullable();
            $table->json('adjustments')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_cards');
    }
};
