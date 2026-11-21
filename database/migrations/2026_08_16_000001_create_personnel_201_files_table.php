<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('personnel_201_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('personnel_id')->constrained('personnel')->cascadeOnDelete();
            $table->string('category', 100);
            $table->string('original_name');
            $table->string('stored_path');
            $table->string('mime_type', 120)->nullable();
            $table->unsignedBigInteger('size_bytes')->default(0);
            $table->text('notes')->nullable();
            $table->string('uploaded_by_type', 20)->nullable();
            $table->unsignedBigInteger('uploaded_by_id')->nullable();
            $table->string('uploaded_by_name')->nullable();
            $table->timestamps();

            $table->index(['personnel_id', 'category']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('personnel_201_files');
    }
};
