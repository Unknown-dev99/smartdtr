<?php

namespace Tests\Feature;

use App\Models\LeaveRequest;
use App\Models\Personnel;
use App\Models\StaffUser;
use App\Services\File201Service;
use App\Services\LeaveBalanceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class NonTeachingServiceRecordTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (Schema::hasTable('leave_requests') && ! Schema::hasTable('leave_requests_data')) {
            Schema::rename('leave_requests', 'leave_requests_data');
        }
    }

    public function test_non_teaching_sees_service_record_and_cto(): void
    {
        $staff = $this->staff('non.teaching@example.test', '00-301', 'non_teaching', 'ADAS II');

        $this->actingAs($staff, 'staff')
            ->get(route('staff.files201.index'))
            ->assertOk()
            ->assertSeeInOrder([
                'Updated Service Record',
                'Compensatory Time Off (CTO)',
                'Save service record',
                'Upload scanned copy',
                'Save CTO',
            ]);

        $this->actingAs($staff, 'staff')
            ->get(route('staff.leave'))
            ->assertOk()
            ->assertSee('Compensatory Time Off (CTO)');

        $categories = app(File201Service::class)->categoriesFor($staff->personnel);
        $this->assertArrayHasKey('updated_service_record', $categories);
        $this->assertArrayHasKey('compensatory_time_off', $categories);
    }

    public function test_non_teaching_can_save_service_record_and_cto(): void
    {
        $staff = $this->staff('records@example.test', '00-304', 'non_teaching', 'ASSP II');

        $this->actingAs($staff, 'staff')
            ->post(route('staff.files201.service-record.store'), [
                'birth_place' => 'Ilagan',
                'entries' => [
                    [
                        'date_from' => '2020-01-06',
                        'date_to' => 'Present',
                        'designation' => 'ASSP II',
                        'status' => 'Permanent',
                        'salary' => '30000',
                        'station' => 'Isabela National High School',
                        'lwop' => '',
                    ],
                ],
            ])
            ->assertRedirect(route('staff.files201.index', ['service_record' => 1]));

        $this->actingAs($staff, 'staff')
            ->post(route('staff.files201.cto.store'), [
                'balance_hours' => '16',
                'entries' => [
                    [
                        'date_from' => '2026-11-16',
                        'date_to' => '2026-11-16',
                        'hours' => '8',
                        'purpose' => 'Offset overtime',
                    ],
                ],
            ])
            ->assertRedirect(route('staff.files201.index', ['cto' => 1]));

        $service = $this->actingAs($staff, 'staff')->get(route('staff.files201.service-record.print'));
        $cto = $this->actingAs($staff, 'staff')->get(route('staff.files201.cto.print'));
        $service->assertOk();
        $cto->assertOk();
        $this->assertStringStartsWith('%PDF', $service->getContent());
        $this->assertStringStartsWith('%PDF', $cto->getContent());
    }

    public function test_teaching_staff_do_not_get_cto(): void
    {
        $staff = $this->staff('teacher.cto@example.test', '00-302', 'teacher', 'TCH III');

        $this->actingAs($staff, 'staff')
            ->get(route('staff.files201.index'))
            ->assertOk()
            ->assertSee('Updated Service Record')
            ->assertDontSee('Compensatory Time Off (CTO)')
            ->assertDontSee('Save service record');

        $this->actingAs($staff, 'staff')
            ->get(route('staff.leave'))
            ->assertOk()
            ->assertDontSee('Compensatory Time Off (CTO)');

        $this->actingAs($staff, 'staff')
            ->post(route('staff.leave.store'), [
                'leave_type' => LeaveRequest::CTO,
                'date_from' => '2026-11-16',
                'date_to' => '2026-11-16',
                'reason' => 'Offset overtime',
            ])
            ->assertSessionHasErrors('leave_type');
    }

    public function test_cto_does_not_deduct_vacation_leave(): void
    {
        $staff = $this->staff('cto.balance@example.test', '00-303', 'non_teaching', 'REGISTRAR');

        $this->actingAs($staff, 'staff')
            ->post(route('staff.leave.store'), [
                'leave_type' => LeaveRequest::CTO,
                'date_from' => '2026-11-16',
                'date_to' => '2026-11-17',
                'reason' => 'Use compensatory time off',
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $request = LeaveRequest::query()->where('staff_user_id', $staff->id)->first();
        $this->assertNotNull($request);
        $this->assertSame('Other', $request->balanceBucket());

        app(LeaveBalanceService::class)->deductForRequest($request);
        $balance = app(LeaveBalanceService::class)->ensureBalance($staff->personnel);

        $this->assertSame(0, (int) $balance->vl_used);
        $this->assertSame(0, (int) $balance->sl_used);
    }

    protected function staff(string $email, string $employeeNumber, string $role, string $position): StaffUser
    {
        $personnel = Personnel::create([
            'name' => 'Sample Staff',
            'role' => $position,
            'employee_number' => $employeeNumber,
            'is_active' => true,
        ]);

        return StaffUser::create([
            'personnel_id' => $personnel->id,
            'name' => 'Sample Staff',
            'last_name' => 'Staff',
            'first_name' => 'Sample',
            'email' => $email,
            'password' => 'Password123!',
            'role' => $role,
            'status' => 'active',
        ]);
    }
}
