<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Allow full CS Form No. 6 leave type labels.
        Schema::table('leave_requests', function (Blueprint $table) {
            $table->string('leave_type', 120)->change();
        });

        Schema::table('leave_requests', function (Blueprint $table) {
            $table->string('office_department')->nullable()->after('personnel_id');
            $table->string('position')->nullable()->after('office_department');
            $table->string('salary')->nullable()->after('position');
            $table->string('leave_details')->nullable()->after('reason');
            $table->string('commutation')->default('not_requested')->after('leave_details'); // not_requested|requested
        });
    }

    public function down(): void
    {
        Schema::table('leave_requests', function (Blueprint $table) {
            $table->dropColumn(['office_department', 'position', 'salary', 'leave_details', 'commutation']);
        });

        // Map expanded types back to legacy enum values before restoring enum.
        DB::table('leave_requests')->orderBy('id')->each(function ($row) {
            $mapped = match (true) {
                str_contains(strtolower((string) $row->leave_type), 'sick') => 'Sick',
                str_contains(strtolower((string) $row->leave_type), 'emergency') => 'Emergency',
                str_contains(strtolower((string) $row->leave_type), 'travel') => 'Travel',
                default => 'Leave',
            };
            DB::table('leave_requests')->where('id', $row->id)->update(['leave_type' => $mapped]);
        });

        Schema::table('leave_requests', function (Blueprint $table) {
            $table->enum('leave_type', ['Leave', 'Travel', 'Sick', 'Emergency', 'Other'])->change();
        });
    }
};
