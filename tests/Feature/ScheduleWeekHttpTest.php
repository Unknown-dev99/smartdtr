<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\Personnel;
use App\Models\Schedule;
use App\Models\StaffUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScheduleWeekHttpTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_saves_monday_through_friday_in_one_post(): void
    {
        $personnel = Personnel::create(['name' => 'Mark Ian Cabaldo Cristobal', 'role' => 'Teacher I']);
        $staff = StaffUser::create([
            'personnel_id' => $personnel->id,
            'name' => 'Mark Ian Cabaldo Cristobal',
            'email' => 'staff-week@example.test',
            'password' => 'Password123!',
            'role' => 'employee',
            'status' => 'active',
        ]);

        $this->actingAs($staff, 'staff')
            ->post(route('staff.schedule.store'), [
                'days' => [1, 2, 3, 4, 5],
                'start_time' => '07:30',
                'end_time' => '17:00',
                'subject' => 'Regular duty',
            ])
            ->assertRedirect();

        $this->assertSame(5, Schedule::where('personnel_id', $personnel->id)->count());
    }

    public function test_admin_applies_school_week_to_unscheduled_employees(): void
    {
        $admin = AdminUser::create([
            'username' => 'sched-admin',
            'email' => 'sched-admin@example.test',
            'password' => 'Password123!',
            'role' => 'super_admin',
        ]);
        $empty = Personnel::create(['name' => 'Unscheduled', 'role' => 'Teacher I', 'is_active' => true]);
        $filled = Personnel::create(['name' => 'Already set', 'role' => 'Teacher I', 'is_active' => true]);
        Schedule::create([
            'personnel_id' => $filled->id,
            'day_of_week' => 2,
            'start_time' => '07:30:00',
            'end_time' => '17:00:00',
            'subject' => 'Regular duty',
        ]);

        $this->actingAs($admin, 'admin')
            ->post(route('admin.schedule.apply-default'))
            ->assertRedirect();

        $this->assertSame(5, Schedule::where('personnel_id', $empty->id)->count());
        $this->assertSame(1, Schedule::where('personnel_id', $filled->id)->count());
    }
}
