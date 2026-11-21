<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ris_slips', function (Blueprint $table) {
            $table->id();
            $table->string('entity_name')->default('INHS-SHS');
            $table->string('fund_cluster')->nullable();
            $table->string('division')->nullable();
            $table->string('office')->nullable();
            $table->string('responsibility_center_code')->nullable();
            $table->string('ris_no')->nullable();
            $table->date('ris_date');
            $table->string('purpose')->nullable();
            $table->string('purpose_line2')->nullable();
            $table->text('purpose_line3')->nullable();
            $table->string('requested_by')->nullable();
            $table->string('requested_designation')->nullable();
            $table->string('approved_by')->nullable();
            $table->string('approved_designation')->nullable();
            $table->string('issued_by')->nullable();
            $table->string('issued_designation')->nullable();
            $table->string('received_by')->nullable();
            $table->string('received_designation')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('admin_users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('ris_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ris_slip_id')->constrained('ris_slips')->cascadeOnDelete();
            $table->unsignedInteger('line_no')->default(1);
            $table->string('stock_no')->nullable();
            $table->string('unit')->nullable();
            $table->string('description');
            $table->decimal('quantity', 12, 2)->default(0);
            $table->string('stock_available')->nullable(); // yes|no|null
            $table->decimal('issue_quantity', 12, 2)->nullable();
            $table->string('remarks')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ris_items');
        Schema::dropIfExists('ris_slips');
    }
};
