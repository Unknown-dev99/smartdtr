<?php

namespace Tests\Unit;

use App\Models\AdminUser;
use App\Models\Attendance;
use App\Models\AttendanceLog;
use App\Models\LeaveRecord;
use App\Models\LocatorRequest;
use App\Models\Personnel;
use App\Models\Schedule;
use App\Models\ScheduleOverride;
use App\Models\StaffUser;
use App\Services\AttendanceService;
use App\Services\DailyStatusService;
use App\Services\DtrExportService;
use App\Services\LocatorRequestService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SmartDtrBusinessLogicTest extends TestCase
{
    use RefreshDatabase;

    protected Personnel $personnel;

    protected DailyStatusService $statusService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->statusService = app(DailyStatusService::class);
        $this->personnel = Personnel::create([
            'name' => 'Test Teacher',
            'role' => 'Teacher I',
            'employee_number' => 'T-001',
        ]);
    }

    public function test_present_when_on_time_with_schedule(): void
    {
        Schedule::create([
            'personnel_id' => $this->personnel->id,
            'day_of_week' => now()->dayOfWeek,
            'start_time' => '07:30:00',
            'end_time' => '16:00:00',
            'subject' => 'Math',
        ]);

        Attendance::create([
            'personnel_id' => $this->personnel->id,
            'date' => now()->toDateString(),
            'time_in' => '07:35:00',
            'time_out' => '17:00:00',
            'status' => 'Unknown',
            'updated_at' => now(),
        ]);

        $result = $this->statusService->computeDailyStatus($this->personnel->id, now()->toDateString());
        $this->assertEquals('Present', $result['status']);
    }

    public function test_late_after_grace_period(): void
    {
        Schedule::create([
            'personnel_id' => $this->personnel->id,
            'day_of_week' => now()->dayOfWeek,
            'start_time' => '07:30:00',
            'end_time' => '16:00:00',
            'subject' => 'Math',
        ]);

        Attendance::create([
            'personnel_id' => $this->personnel->id,
            'date' => now()->toDateString(),
            'time_in' => '07:45:00',
            'time_out' => '17:00:00',
            'status' => 'Unknown',
            'updated_at' => now(),
        ]);

        $result = $this->statusService->computeDailyStatus($this->personnel->id, now()->toDateString());
        $this->assertEquals('Late', $result['status']);
    }

    public function test_on_leave_without_attendance(): void
    {
        $date = now()->toDateString();
        LeaveRecord::create([
            'personnel_id' => $this->personnel->id,
            'date' => $date,
            'leave_type' => 'Leave',
        ]);

        $result = $this->statusService->computeDailyStatus($this->personnel->id, $date);
        $this->assertEquals('On Leave', $result['status']);
    }

    public function test_off_duty_without_schedule(): void
    {
        $date = now()->toDateString();
        $result = $this->statusService->computeDailyStatus($this->personnel->id, $date);
        $this->assertEquals('Off-duty', $result['status']);
    }

    public function test_class_override_off_duty_with_scan_is_present(): void
    {
        $date = now()->toDateString();
        ScheduleOverride::create([
            'personnel_id' => $this->personnel->id,
            'date' => $date,
            'override_type' => 'Off-duty',
        ]);

        Attendance::create([
            'personnel_id' => $this->personnel->id,
            'date' => $date,
            'time_in' => '08:00:00',
            'time_out' => '17:00:00',
            'status' => 'Unknown',
            'updated_at' => now(),
        ]);

        $result = $this->statusService->computeDailyStatus($this->personnel->id, $date);
        $this->assertEquals('Present', $result['status']);
    }

    public function test_dtr_four_scan_mapping(): void
    {
        $service = app(DtrExportService::class);
        $mapped = $service->mapScansToDtr(['07:30:00', '12:00:00', '13:00:00', '17:00:00']);

        $this->assertEquals('07:30:00', $mapped['am_arrival']);
        $this->assertEquals('12:00:00', $mapped['am_departure']);
        $this->assertEquals('13:00:00', $mapped['pm_arrival']);
        $this->assertEquals('17:00:00', $mapped['pm_departure']);
    }

    public function test_dtr_two_scan_mapping(): void
    {
        $service = app(DtrExportService::class);
        $mapped = $service->mapScansToDtr(['07:30:00', '17:00:00']);

        $this->assertEquals('07:30:00', $mapped['am_arrival']);
        $this->assertEquals('17:00:00', $mapped['am_departure']);
        $this->assertNull($mapped['pm_arrival']);
        $this->assertNull($mapped['pm_departure']);
    }

    public function test_dtr_scans_fill_form_48_in_order_including_overtime(): void
    {
        $service = app(DtrExportService::class);
        $mapped = $service->mapScansToDtr([
            '08:40:00', '09:53:00', '13:05:00', '17:02:00', '17:40:00', '19:15:00',
        ]);

        $this->assertEquals('08:40:00', $mapped['am_arrival']);
        $this->assertEquals('09:53:00', $mapped['am_departure']);
        $this->assertEquals('13:05:00', $mapped['pm_arrival']);
        $this->assertEquals('17:02:00', $mapped['pm_departure']);
        $this->assertEquals('17:40:00', $mapped['ot_arrival']);
        $this->assertEquals('19:15:00', $mapped['ot_departure']);

        $five = $service->mapScansToDtr(['08:40:00', '09:53:00', '13:05:00', '17:02:00', '17:40:00']);
        $this->assertEquals('17:40:00', $five['ot_arrival']);
        $this->assertNull($five['ot_departure']);
    }

    public function test_dtr_three_scan_lunch_mapping(): void
    {
        $service = app(DtrExportService::class);
        $mapped = $service->mapScansToDtr(['07:30:00', '12:00:00', '17:00:00']);

        $this->assertEquals('07:30:00', $mapped['am_arrival']);
        $this->assertEquals('12:00:00', $mapped['am_departure']);
        $this->assertEquals('17:00:00', $mapped['pm_arrival']);
        $this->assertNull($mapped['pm_departure']);
    }

    public function test_attendance_upsert_dedupe(): void
    {
        $service = app(AttendanceService::class);
        $date = now()->toDateString();

        $first = $service->processScan([
            'personnel_id' => $this->personnel->id,
            'date' => $date,
            'time' => '07:30:00',
            'blink_detected' => true,
            'face_confidence' => 0.9,
        ]);

        $duplicate = $service->processScan([
            'personnel_id' => $this->personnel->id,
            'date' => $date,
            'time' => '07:31:00',
            'blink_detected' => true,
            'face_confidence' => 0.9,
        ]);

        $timeout = $service->processScan([
            'personnel_id' => $this->personnel->id,
            'date' => $date,
            'time' => '17:00:00',
            'blink_detected' => true,
            'face_confidence' => 0.9,
        ]);

        $this->assertTrue($first['success']);
        $this->assertFalse($first['data']['already_attended']);
        $this->assertTrue($duplicate['success']);
        $this->assertTrue($duplicate['data']['already_attended']);
        $this->assertEquals('Already attended!', $duplicate['data']['status']);
        $this->assertEquals('07:30:00', $duplicate['data']['time_in']);
        $this->assertEquals('07:30:00', $duplicate['data']['time_out']);
        $this->assertFalse($timeout['data']['already_attended']);
        $this->assertEquals('17:00:00', $timeout['data']['time_out']);

        $attendance = Attendance::where('personnel_id', $this->personnel->id)->whereDate('date', $date)->first();
        $this->assertEquals('07:30:00', $attendance->time_in);
        $this->assertEquals('17:00:00', $attendance->time_out);
        $this->assertNotEquals('Already attended!', $attendance->status);
        $this->assertEquals(3, AttendanceLog::where('personnel_id', $this->personnel->id)->count());
    }

    public function test_scan_rejected_without_blink(): void
    {
        $service = app(AttendanceService::class);
        $result = $service->processScan([
            'personnel_id' => $this->personnel->id,
            'blink_detected' => false,
            'face_confidence' => 0.9,
        ]);

        $this->assertFalse($result['success']);
        $this->assertEquals(403, $result['status']);
    }

    public function test_manual_correction_updates_dtr_export_times(): void
    {
        $date = now()->startOfMonth()->toDateString();
        $month = now()->format('Y-m');

        AttendanceLog::create([
            'personnel_id' => $this->personnel->id,
            'date' => $date,
            'time' => '07:30:00',
            'created_at' => now(),
        ]);
        AttendanceLog::create([
            'personnel_id' => $this->personnel->id,
            'date' => $date,
            'time' => '17:00:00',
            'created_at' => now(),
        ]);
        Attendance::create([
            'personnel_id' => $this->personnel->id,
            'date' => $date,
            'time_in' => '07:30:00',
            'time_out' => '17:00:00',
            'status' => 'Unknown',
            'updated_at' => now(),
        ]);

        app(AttendanceService::class)->applyManualCorrection(
            $this->personnel->id,
            $date,
            '08:00',
            '16:30'
        );

        $day = (int) now()->startOfMonth()->format('j');
        $map = app(DtrExportService::class)->buildMonthlyDayMap($this->personnel->id, $month);

        $this->assertEquals('8:00 AM', $map[$day]['am_arrival']);
        $this->assertEquals('4:30 PM', $map[$day]['am_departure']);
        $this->assertEquals(2, AttendanceLog::where('personnel_id', $this->personnel->id)->whereDate('date', $date)->count());
    }

    public function test_approved_locator_keeps_am_punches_and_marks_pm_ob(): void
    {
        $date = now()->startOfMonth()->toDateString();
        $this->createApprovedLocator($date, $date);

        Attendance::create([
            'personnel_id' => $this->personnel->id,
            'date' => $date,
            'time_in' => '07:30:00',
            'time_out' => null,
            'status' => 'Unknown',
            'updated_at' => now(),
        ]);
        AttendanceLog::create([
            'personnel_id' => $this->personnel->id,
            'date' => $date,
            'time' => '07:30:00',
            'created_at' => now(),
        ]);

        $result = $this->statusService->computeDailyStatus($this->personnel->id, $date);
        $this->assertEquals('Official Business', $result['status']);
        $this->assertEquals('07:30:00', $result['time_in']);

        $map = app(DtrExportService::class)->buildMonthlyDayMap($this->personnel->id, now()->format('Y-m'));
        $day = 1;
        $this->assertEquals('7:30 AM', $map[$day]['am_arrival']);
        $this->assertEquals('OB', $map[$day]['pm_arrival']);
        $this->assertNull($map[$day]['pm_departure']);
    }

    public function test_approved_locator_keeps_pm_punches_and_marks_am_ob(): void
    {
        $date = now()->startOfMonth()->toDateString();
        $this->createApprovedLocator($date, $date);

        Attendance::create([
            'personnel_id' => $this->personnel->id,
            'date' => $date,
            'time_in' => '13:00:00',
            'time_out' => '17:00:00',
            'status' => 'Unknown',
            'updated_at' => now(),
        ]);
        AttendanceLog::create([
            'personnel_id' => $this->personnel->id,
            'date' => $date,
            'time' => '13:00:00',
            'created_at' => now(),
        ]);
        AttendanceLog::create([
            'personnel_id' => $this->personnel->id,
            'date' => $date,
            'time' => '17:00:00',
            'created_at' => now(),
        ]);

        $map = app(DtrExportService::class)->buildMonthlyDayMap($this->personnel->id, now()->format('Y-m'));
        $this->assertEquals('OB', $map[1]['am_arrival']);
        $this->assertEquals('1:00 PM', $map[1]['pm_arrival']);
        $this->assertEquals('5:00 PM', $map[1]['pm_departure']);
    }

    public function test_approved_locator_without_punches_is_full_day_ob(): void
    {
        $date = now()->startOfMonth()->toDateString();
        $this->createApprovedLocator($date, $date);

        $result = $this->statusService->computeDailyStatus($this->personnel->id, $date);
        $this->assertEquals('Official Business', $result['status']);
        $this->assertNull($result['time_in']);

        $map = app(DtrExportService::class)->buildMonthlyDayMap($this->personnel->id, now()->format('Y-m'));
        $this->assertEquals('OB', $map[1]['label']);
    }

    public function test_locator_approve_persists_official_business_for_date_range(): void
    {
        $from = now()->startOfMonth()->toDateString();
        $to = now()->startOfMonth()->addDay()->toDateString();
        $locator = $this->createApprovedLocator($from, $to, persistApproved: false);
        $admin = AdminUser::create([
            'username' => 'locator-admin',
            'email' => 'locator-admin@example.test',
            'password' => 'Password123!',
            'role' => 'super_admin',
        ]);

        app(LocatorRequestService::class)->approve($locator, $admin->id);

        $this->assertEquals('Official Business', Attendance::where('personnel_id', $this->personnel->id)->whereDate('date', $from)->value('status'));
        $this->assertEquals('Official Business', Attendance::where('personnel_id', $this->personnel->id)->whereDate('date', $to)->value('status'));
    }

    protected function createApprovedLocator(string $from, string $to, bool $persistApproved = true): LocatorRequest
    {
        $staff = StaffUser::create([
            'personnel_id' => $this->personnel->id,
            'name' => 'Locator Staff',
            'email' => 'locator-staff-'.uniqid().'@example.test',
            'password' => 'Password123!',
            'role' => 'teacher',
            'status' => 'active',
        ]);

        return LocatorRequest::create([
            'staff_user_id' => $staff->id,
            'personnel_id' => $this->personnel->id,
            'date' => $from,
            'date_to' => $to,
            'time_out' => '13:00:00',
            'time_in_expected' => '17:00:00',
            'destination' => 'Division Office',
            'purpose' => 'Official meeting',
            'status' => $persistApproved ? 'approved' : 'pending',
        ]);
    }
}
