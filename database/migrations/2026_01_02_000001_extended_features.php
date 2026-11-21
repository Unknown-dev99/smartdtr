<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('admin_users', function (Blueprint $table) {
            $table->string('email')->nullable()->unique()->after('username');
            $table->string('role')->default('super_admin')->after('password');
            $table->string('reset_token')->nullable()->after('role');
            $table->timestamp('reset_token_expires_at')->nullable()->after('reset_token');
        });

        Schema::table('personnel', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('face_dataset_dir');
            $table->timestamp('deactivated_at')->nullable()->after('is_active');
        });

        Schema::create('holidays', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->date('date');
            $table->enum('type', ['holiday', 'suspension', 'non_working'])->default('holiday');
            $table->foreignId('created_by')->nullable()->constrained('admin_users')->nullOnDelete();
            $table->timestamps();
            $table->unique('date');
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->string('actor_type');
            $table->unsignedBigInteger('actor_id')->nullable();
            $table->string('actor_name')->nullable();
            $table->string('action');
            $table->string('subject_type')->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->text('description')->nullable();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();
            $table->index(['subject_type', 'subject_id']);
            $table->index('created_at');
        });

        Schema::create('leave_balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('personnel_id')->constrained('personnel')->cascadeOnDelete();
            $table->decimal('vl_balance', 6, 1)->default(15);
            $table->decimal('vl_used', 6, 1)->default(0);
            $table->decimal('sl_balance', 6, 1)->default(15);
            $table->decimal('sl_used', 6, 1)->default(0);
            $table->timestamps();
            $table->unique('personnel_id');
        });

        Schema::create('device_heartbeats', function (Blueprint $table) {
            $table->id();
            $table->string('device_name')->default('raspberry-pi-kiosk');
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamp('last_scan_at')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->unsignedInteger('scan_count_today')->default(0);
            $table->date('scan_count_date')->nullable();
            $table->timestamps();
            $table->unique('device_name');
        });

        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('device_heartbeats');
        Schema::dropIfExists('leave_balances');
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('holidays');

        Schema::table('personnel', function (Blueprint $table) {
            $table->dropColumn(['is_active', 'deactivated_at']);
        });

        Schema::table('admin_users', function (Blueprint $table) {
            $table->dropColumn(['email', 'role', 'reset_token', 'reset_token_expires_at']);
        });
    }
};
