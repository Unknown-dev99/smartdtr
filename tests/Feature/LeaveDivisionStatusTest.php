<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Models\Personnel;
use App\Models\StaffUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class LeaveDivisionStatusTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (Schema::hasTable('leave_requests') && ! Schema::hasTable('leave_requests_data')) {
            Schema::rename('leave_requests', 'leave_requests_data');
        }
    }

    public function test_school_can_mark_leave_pending_division_office_approval(): void
    {
        $staff = $this->staff();
        $leave = LeaveRequest::create([
            'staff_user_id' => $staff->id,
            'personnel_id' => $staff->personnel_id,
            'leave_type' => 'Vacation Leave',
            'date_from' => '2026-11-16',
            'date_to' => '2026-11-17',
            'reason' => 'Personal errand',
            'status' => 'pending',
        ]);
        $admin = AdminUser::create([
            'username' => 'division-hr',
            'email' => 'division-hr@example.test',
            'password' => 'Password123!',
            'role' => 'super_admin',
        ]);

        $this->actingAs($admin, 'admin')
            ->post(route('admin.hr.leave.division', $leave), ['admin_notes' => 'Endorsed to the Division Office'])
            ->assertRedirect();

        $leave->refresh();
        $this->assertSame(LeaveRequest::STATUS_DIVISION, $leave->status);
        $this->assertSame(0, (int) LeaveBalance::query()->where('personnel_id', $staff->personnel_id)->value('vl_used'));

        $this->actingAs($admin, 'admin')
            ->get(route('admin.hr.leave-requests'))
            ->assertOk()
            ->assertSee('Pending Division Office approval')
            ->assertSee('Approve');

        $this->actingAs($staff, 'staff')
            ->get(route('staff.leave'))
            ->assertOk()
            ->assertSee('Pending Division Office approval');
    }

    protected function staff(): StaffUser
    {
        $personnel = Personnel::create([
            'name' => 'Emelinda C Abu',
            'role' => 'ASSP II',
            'employee_number' => '00-401',
            'is_active' => true,
        ]);

        return StaffUser::create([
            'personnel_id' => $personnel->id,
            'name' => 'Emelinda C Abu',
            'last_name' => 'Abu',
            'first_name' => 'Emelinda',
            'email' => 'emelinda.division@example.test',
            'password' => 'Password123!',
            'role' => 'non_teaching',
            'status' => 'active',
        ]);
    }
}
