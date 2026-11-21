<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rsmi_reports', function (Blueprint $table) {
            $table->id();
            $table->string('entity_name')->default('Isabela National High School');
            $table->string('fund_cluster')->nullable();
            $table->string('serial_no')->nullable();
            $table->date('report_date');
            $table->string('supply_custodian')->nullable();
            $table->string('accounting_staff')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('admin_users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('rsmi_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rsmi_report_id')->constrained('rsmi_reports')->cascadeOnDelete();
            $table->unsignedInteger('line_no')->default(1);
            $table->string('ris_no')->nullable();
            $table->string('responsibility_center')->nullable();
            $table->string('stock_no')->nullable();
            $table->string('item');
            $table->string('unit')->nullable();
            $table->decimal('quantity', 12, 2)->default(0);
            $table->decimal('unit_cost', 12, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rsmi_items');
        Schema::dropIfExists('rsmi_reports');
    }
};
