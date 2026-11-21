<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('locator_requests', function (Blueprint $table) {
            if (! Schema::hasColumn('locator_requests', 'host_of_activity')) {
                $table->string('host_of_activity')->nullable()->after('purpose');
            }
            if (! Schema::hasColumn('locator_requests', 'fund_source')) {
                $table->string('fund_source')->nullable()->after('destination');
            }
            if (! Schema::hasColumn('locator_requests', 'date_to')) {
                $table->date('date_to')->nullable()->after('date');
            }
            if (! Schema::hasColumn('locator_requests', 'position_designation')) {
                $table->string('position_designation')->nullable()->after('personnel_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('locator_requests', function (Blueprint $table) {
            foreach (['host_of_activity', 'fund_source', 'date_to', 'position_designation'] as $col) {
                if (Schema::hasColumn('locator_requests', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
