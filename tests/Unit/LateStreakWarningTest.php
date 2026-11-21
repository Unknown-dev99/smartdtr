<?php

namespace Tests\Unit;

use App\Models\Attendance;
use App\Models\Holiday;
use App\Models\Personnel;
use App\Models\Schedule;
use App\Models\StaffUser;
use App\Services\DailyStatusService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LateStreakWarningTest extends TestCase
{
    use RefreshDatabase;

    protected Personnel $personnel;

    protected StaffUser $staff;

    protected DailyStatusService $statusService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->statusService = app(DailyStatusService::class);
        $this->personnel = Personnel::create([
            'name' => 'Late Teacher',
            'role' => 'Teacher I',
            'employee_number' => 'T-LATE',
        ]);
        $this->staff = StaffUser::create([
            'personnel_id' => $this->personnel->id,
            'name' => 'Late Teacher',
            'email' => 'late.teacher@example.test',
            'password' => 'Password123!',
            'role' => 'teacher',
            'status' => 'active',
        ]);

        foreach (range(1, 5) as $day) {
            Schedule::create([
                'personnel_id' => $this->personnel->id,
                'day_of_week' => $day,
                'start_time' => '07:30:00',
                'end_time' => '16:00:00',
                'subject' => 'Duty',
            ]);
        }
    }

    public function test_warning_is_sent_after_three_straight_late_work_days(): void
    {
        $this->markLate('2026-03-02');
        $this->markLate('2026-03-03');
        $this->assertSame(0, $this->staff->notifications()->count());

        $this->markLate('2026-03-04');

        $this->staff->refresh();
        $notifications = $this->staff->notifications()->get();
        $this->assertCount(1, $notifications);
        $this->assertSame('Late attendance warning', $notifications[0]->data['title']);
        $this->assertStringContainsString('3 straight work days', $notifications[0]->data['message']);
        $this->assertStringContainsString('Mar 2, 2026', $notifications[0]->data['message']);
        $this->assertStringContainsString('Mar 4, 2026', $notifications[0]->data['message']);
        $this->assertSame('late-streak:'.$this->personnel->id.':2026-03-02', $notifications[0]->data['key']);
    }

    public function test_the_same_streak_is_not_warned_twice(): void
    {
        $this->markLate('2026-03-02');
        $this->markLate('2026-03-03');
        $this->markLate('2026-03-04');
        $this->markLate('2026-03-05');
        $this->markLate('2026-03-04');

        $this->assertSame(1, $this->staff->fresh()->notifications()->count());
    }

    public function test_a_present_day_breaks_the_streak(): void
    {
        $this->markLate('2026-03-02');
        $this->markLate('2026-03-03');
        $this->markArrival('2026-03-04', '07:30:00');
        $this->markLate('2026-03-05');
        $this->markLate('2026-03-06');

        $this->assertSame(0, $this->staff->fresh()->notifications()->count());
    }

    public function test_weekend_does_not_break_three_straight_work_days(): void
    {
        $this->markLate('2026-03-06');
        $this->markLate('2026-03-09');
        $this->markLate('2026-03-10');

        $notification = $this->staff->fresh()->notifications()->first();
        $this->assertNotNull($notification);
        $this->assertStringContainsString('Mar 6, 2026', $notification->data['message']);
        $this->assertStringContainsString('Mar 10, 2026', $notification->data['message']);
    }

    public function test_holiday_does_not_break_the_streak(): void
    {
        Holiday::create([
            'name' => 'School holiday',
            'date' => '2026-03-04',
            'type' => 'holiday',
        ]);

        $this->markLate('2026-03-02');
        $this->markLate('2026-03-03');
        $this->markLate('2026-03-05');

        $this->assertSame(1, $this->staff->fresh()->notifications()->count());
    }

    protected function markLate(string $date): void
    {
        $this->markArrival($date, '08:00:00');
    }

    protected function markArrival(string $date, string $timeIn): void
    {
        Attendance::updateOrCreateForDate($this->personnel->id, $date, [
            'time_in' => $timeIn,
            'time_out' => '17:00:00',
            'updated_at' => now(),
        ]);

        $result = $this->statusService->computeAndPersist($this->personnel->id, $date);
        $expected = Carbon::parse($timeIn)->gt(Carbon::parse('07:40:00')) ? 'Late' : 'Present';
        $this->assertSame($expected, $result['status']);
    }
}
