<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ris_items', function (Blueprint $table) {
            $table->decimal('unit_cost', 12, 2)->default(0)->after('issue_quantity');
        });

        Schema::table('rsmi_items', function (Blueprint $table) {
            $table->foreignId('ris_item_id')
                ->nullable()
                ->after('rsmi_report_id')
                ->constrained('ris_items')
                ->nullOnDelete();
            $table->unique('ris_item_id');
        });
    }

    public function down(): void
    {
        Schema::table('rsmi_items', function (Blueprint $table) {
            $table->dropUnique(['ris_item_id']);
            $table->dropConstrainedForeignId('ris_item_id');
        });

        Schema::table('ris_items', function (Blueprint $table) {
            $table->dropColumn('unit_cost');
        });
    }
};
