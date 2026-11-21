<?php

namespace Tests\Unit;

use App\Services\ReportExportService;
use Tests\TestCase;

class AttendancePdfLayoutTest extends TestCase
{
    public function test_pdf_layout_splits_weeks_and_names(): void
    {
        $dates = [];
        for ($day = 1; $day <= 31; $day++) {
            $date = sprintf('2026-09-%02d', $day);
            $dates[] = [
                'date' => $date,
                'label' => 'SEPTEMBER '.$day.',2026',
                'short' => 'Sep '.$day,
                'weekday' => 'Mon',
            ];
        }

        $people = [];
        for ($i = 1; $i <= 85; $i++) {
            $days = [];
            foreach ($dates as $day) {
                $days[$day['date']] = [
                    'time_in' => $i === 1 && $day['date'] === '2026-09-19' ? '7:30AM' : null,
                    'time_out' => $i === 1 && $day['date'] === '2026-09-19' ? '5:00PM' : null,
                    'status' => $i === 1 && $day['date'] === '2026-09-19' ? 'Present' : 'Absent',
                ];
            }
            $people[] = [
                'personnel_id' => $i,
                'name' => 'Employee '.$i,
                'role' => 'Teacher',
                'days' => $days,
            ];
        }

        $layout = app(ReportExportService::class)->buildPrintableAttendanceLayout(
            ['dates' => $dates, 'people' => $people],
            '2026-09-01',
            '2026-09-30'
        );

        $this->assertSame(85, $layout['personnel_count']);
        $this->assertSame(5, $layout['sheets'][0]['week_count']);
        $this->assertCount(7, $layout['sheets'][0]['dates']);
        $this->assertCount(28, $layout['sheets'][0]['people']);
        $this->assertSame(5 * 4, count($layout['sheets']));
        $sep19 = collect($layout['sheets'])->first(
            fn (array $sheet) => collect($sheet['dates'])->contains(fn ($day) => $day['date'] === '2026-09-19')
                && str_starts_with($sheet['name_label'], 'Names 1–')
        );
        $this->assertNotNull($sep19);
        $this->assertSame('7:30AM', $sep19['people'][0]['days']['2026-09-19']['in']);
        $this->assertGreaterThan(0, $layout['summary']['Absent']);
    }

    public function test_pdf_cell_uses_status_code_when_no_times(): void
    {
        $service = app(ReportExportService::class);

        $this->assertSame(['in' => 'L', 'out' => ''], $service->pdfCell([
            'time_in' => null,
            'time_out' => null,
            'status' => 'On Leave',
        ]));
        $this->assertSame(['in' => '7:30AM', 'out' => '5:00PM'], $service->pdfCell([
            'time_in' => '7:30AM',
            'time_out' => '5:00PM',
            'status' => 'Present',
        ]));
    }
}
