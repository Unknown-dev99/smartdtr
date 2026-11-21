<?php

namespace Tests\Unit;

use App\Models\Personnel;
use App\Models\Schedule;
use App\Services\ScheduleWeekService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScheduleWeekServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_assign_week_creates_one_row_per_selected_day(): void
    {
        $person = Personnel::create(['name' => 'Teacher A', 'role' => 'Teacher I']);

        $saved = app(ScheduleWeekService::class)->assignWeek(
            [$person->id],
            [1, 2, 3, 4, 5],
            '07:30',
            '17:00',
            'Regular duty'
        );

        $this->assertSame(5, $saved);
        $this->assertSame(5, Schedule::where('personnel_id', $person->id)->count());
        $this->assertSame('07:30:00', Schedule::where('day_of_week', 1)->value('start_time'));
        $this->assertSame('17:00:00', Schedule::where('day_of_week', 1)->value('end_time'));
    }

    public function test_assign_week_replaces_existing_rows_for_those_days(): void
    {
        $person = Personnel::create(['name' => 'Teacher B', 'role' => 'Teacher I']);
        Schedule::create([
            'personnel_id' => $person->id,
            'day_of_week' => 1,
            'start_time' => '08:00:00',
            'end_time' => '09:00:00',
            'subject' => 'Math',
        ]);
        Schedule::create([
            'personnel_id' => $person->id,
            'day_of_week' => 1,
            'start_time' => '13:00:00',
            'end_time' => '14:00:00',
            'subject' => 'Homeroom',
        ]);

        app(ScheduleWeekService::class)->assignWeek(
            [$person->id],
            [1],
            '07:30',
            '17:00',
            'Regular duty'
        );

        $monday = Schedule::where('personnel_id', $person->id)->where('day_of_week', 1)->get();
        $this->assertCount(1, $monday);
        $this->assertSame('Regular duty', $monday->first()->subject);
    }

    public function test_school_default_skips_people_who_already_have_a_week(): void
    {
        $empty = Personnel::create(['name' => 'No Schedule', 'role' => 'Teacher I', 'is_active' => true]);
        $filled = Personnel::create(['name' => 'Has Schedule', 'role' => 'Teacher I', 'is_active' => true]);
        Schedule::create([
            'personnel_id' => $filled->id,
            'day_of_week' => 1,
            'start_time' => '07:30:00',
            'end_time' => '17:00:00',
            'subject' => 'Regular duty',
        ]);

        $saved = app(ScheduleWeekService::class)->applySchoolDefaultToUnscheduled();

        $this->assertSame(5, $saved);
        $this->assertSame(5, Schedule::where('personnel_id', $empty->id)->count());
        $this->assertSame(1, Schedule::where('personnel_id', $filled->id)->count());
    }

    public function test_copy_week_to_other_employees(): void
    {
        $source = Personnel::create(['name' => 'Source', 'role' => 'Teacher I']);
        $target = Personnel::create(['name' => 'Target', 'role' => 'Teacher I']);
        app(ScheduleWeekService::class)->assignWeek(
            [$source->id],
            [1, 5],
            '07:30',
            '16:00',
            'SHS duty'
        );

        $saved = app(ScheduleWeekService::class)->copyWeek($source->id, [$target->id, $source->id]);

        $this->assertSame(2, $saved);
        $this->assertSame(2, Schedule::where('personnel_id', $target->id)->count());
        $this->assertSame('SHS duty', Schedule::where('personnel_id', $target->id)->where('day_of_week', 5)->value('subject'));
    }
}
