<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Personnel;
use App\Models\PersonnelDailyStatus;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ReportExportService
{
    public function __construct(
        protected DailyStatusService $dailyStatusService
    ) {}

    /**
     * @return array{counts:array<string,int>,records:array<int,array<string,mixed>>}
     */
    public function buildDailyReport(string $date): array
    {
        $personnel = Personnel::orderBy('name')->get();
        $records = [];
        $counts = [
            'Present' => 0,
            'Late' => 0,
            'Absent' => 0,
            'On Leave' => 0,
            'Official Travel' => 0,
            'Official Business' => 0,
            'Off-duty' => 0,
        ];

        foreach ($personnel as $person) {
            $status = $this->dailyStatusService->computeDailyStatus($person->id, $date);

            if (isset($counts[$status['status']])) {
                $counts[$status['status']]++;
            }

            $records[] = [
                'id' => $person->id,
                'name' => $person->name,
                'role' => $person->role,
                'status' => $status['status'],
                'time_in' => $status['time_in'],
                'time_out' => $status['time_out'],
                'employee_number' => $person->employee_number,
            ];
        }

        return ['counts' => $counts, 'records' => $records, 'date' => $date];
    }

    /**
     * Flat rows (API / legacy): one entry per personnel per date.
     *
     * @return array<int, array<string, mixed>>
     */
    public function buildRangeReport(string $from, string $to): array
    {
        $matrix = $this->buildWideAttendanceMatrix($from, $to);
        $rows = [];

        foreach ($matrix['people'] as $person) {
            foreach ($matrix['dates'] as $day) {
                $cell = $person['days'][$day['date']] ?? ['time_in' => null, 'time_out' => null, 'status' => null];
                $rows[] = [
                    'date' => $day['date'],
                    'date_label' => $day['label'],
                    'personnel_id' => $person['personnel_id'],
                    'name' => $person['name'],
                    'role' => $person['role'],
                    'status' => $cell['status'],
                    'time_in' => $cell['time_in'],
                    'time_out' => $cell['time_out'],
                ];
            }
        }

        return $rows;
    }

    /**
     * Wide matrix matching the school attendance sheet:
     * NAME | [DATE → IN, OUT] | [DATE → IN, OUT] | …
     * One data row per personnel.
     *
     * @return array{
     *   dates: list<array{date:string,label:string}>,
     *   people: list<array{personnel_id:int,name:string,role:?string,days:array<string,array{time_in:?string,time_out:?string,status:?string}>}>
     * }
     */
    public function buildWideAttendanceMatrix(string $from, string $to): array
    {
        $fromDate = Carbon::parse($from)->startOfDay();
        $toDate = Carbon::parse($to)->startOfDay();
        $maxDays = config('smartdtr.max_report_range_days', 31);

        if ($fromDate->diffInDays($toDate) + 1 > $maxDays) {
            throw new \InvalidArgumentException("Date range cannot exceed {$maxDays} days.");
        }

        $dates = [];
        for ($date = $fromDate->copy(); $date->lte($toDate); $date->addDay()) {
            $dates[] = [
                'date' => $date->toDateString(),
                // Match reference: SEPTEMBER 7,2026 (no space after comma)
                'label' => strtoupper($date->format('F j,Y')),
                'short' => $date->format('M j'),
                'weekday' => $date->format('D'),
            ];
        }

        $personnel = Personnel::active()->orderBy('name')->get();
        if ($personnel->isEmpty()) {
            $personnel = Personnel::orderBy('name')->get();
        }

        $people = [];
        foreach ($personnel as $person) {
            $days = [];
            foreach ($dates as $day) {
                $dateStr = $day['date'];
                $status = PersonnelDailyStatus::where('personnel_id', $person->id)
                    ->whereDate('date', $dateStr)
                    ->first();

                if (! $status) {
                    $computed = $this->dailyStatusService->computeDailyStatus($person->id, $dateStr);
                    $statusValue = $computed['status'];
                    $timeIn = $computed['time_in'];
                    $timeOut = $computed['time_out'];
                } else {
                    $attendance = Attendance::where('personnel_id', $person->id)->whereDate('date', $dateStr)->first();
                    $statusValue = $status->status;
                    $timeIn = $attendance?->time_in;
                    $timeOut = $attendance?->time_out;
                }

                $days[$dateStr] = [
                    'time_in' => $this->formatReportTime($timeIn),
                    'time_out' => $this->formatReportTime($timeOut),
                    'status' => $statusValue,
                ];
            }

            $people[] = [
                'personnel_id' => $person->id,
                'name' => $person->name,
                'role' => $person->role,
                'days' => $days,
            ];
        }

        return [
            'dates' => $dates,
            'people' => $people,
        ];
    }

    public const PDF_DAYS_PER_SHEET = 7;

    public const PDF_NAMES_PER_SHEET = 28;

    /**
     * Print layout for ~85 personnel: summary page + weekly sheets
     * (7 days × 28 names) on legal landscape.
     *
     * @param  array{dates:list,people:list}  $matrix
     * @return array{
     *   school:string,
     *   from_label:string,
     *   to_label:string,
     *   personnel_count:int,
     *   summary:array<string,int>,
     *   sheets:list<array{week_label:string,name_label:string,dates:list,people:list}>
     * }
     */
    public function buildPrintableAttendanceLayout(array $matrix, string $from, string $to): array
    {
        $dates = $matrix['dates'] ?? [];
        $people = $matrix['people'] ?? [];
        $totalPeople = count($people);

        $summary = [
            'Present' => 0,
            'Late' => 0,
            'Absent' => 0,
            'On Leave' => 0,
            'Official Travel' => 0,
            'Official Business' => 0,
            'Off-duty' => 0,
        ];

        foreach ($people as $person) {
            foreach ($dates as $day) {
                $status = $person['days'][$day['date']]['status'] ?? null;
                if (is_string($status) && isset($summary[$status])) {
                    $summary[$status]++;
                }
            }
        }

        $weeks = array_chunk($dates, self::PDF_DAYS_PER_SHEET);
        $nameGroups = array_chunk($people, self::PDF_NAMES_PER_SHEET);
        $sheets = [];

        foreach ($weeks as $weekIndex => $weekDates) {
            $first = Carbon::parse($weekDates[0]['date']);
            $last = Carbon::parse($weekDates[array_key_last($weekDates)]['date']);
            $weekLabel = $first->format('M j').' – '.$last->format('M j, Y');

            foreach ($nameGroups as $groupIndex => $group) {
                $start = ($groupIndex * self::PDF_NAMES_PER_SHEET) + 1;
                $end = $start + count($group) - 1;
                $sheets[] = [
                    'week_label' => $weekLabel,
                    'week_no' => $weekIndex + 1,
                    'week_count' => count($weeks),
                    'name_label' => $totalPeople === 0
                        ? 'No personnel'
                        : 'Names '.$start.'–'.$end.' of '.$totalPeople,
                    'dates' => $weekDates,
                    'people' => array_map(function (array $person) use ($weekDates) {
                        $days = [];
                        foreach ($weekDates as $day) {
                            $days[$day['date']] = $this->pdfCell($person['days'][$day['date']] ?? []);
                        }

                        return [
                            'name' => $person['name'],
                            'role' => $person['role'] ?? '',
                            'days' => $days,
                        ];
                    }, $group),
                ];
            }
        }

        return [
            'school' => 'Isabela National High School – Senior High School',
            'from_label' => Carbon::parse($from)->format('F j, Y'),
            'to_label' => Carbon::parse($to)->format('F j, Y'),
            'personnel_count' => $totalPeople,
            'summary' => $summary,
            'sheets' => $sheets,
        ];
    }

    /**
     * @param  array{time_in?:?string,time_out?:?string,status?:?string}  $cell
     * @return array{in:string,out:string}
     */
    public function pdfCell(array $cell): array
    {
        $in = trim((string) ($cell['time_in'] ?? ''));
        $out = trim((string) ($cell['time_out'] ?? ''));
        if ($in !== '' || $out !== '') {
            return ['in' => $in, 'out' => $out];
        }

        $code = match ($cell['status'] ?? '') {
            'On Leave' => 'L',
            'Official Travel' => 'OT',
            'Official Business' => 'OB',
            'Absent' => 'A',
            'Off-duty' => 'OD',
            default => '',
        };

        return ['in' => $code, 'out' => ''];
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<int, array{date:string,date_label:string,records:array<int, array<string, mixed>>}>
     */
    public function groupRowsByDate(array $rows): array
    {
        $groups = [];
        foreach ($rows as $row) {
            $key = (string) $row['date'];
            if (! isset($groups[$key])) {
                $groups[$key] = [
                    'date' => $key,
                    'date_label' => $row['date_label'] ?? Carbon::parse($key)->format('F j, Y'),
                    'records' => [],
                ];
            }
            $groups[$key]['records'][] = $row;
        }

        return array_values($groups);
    }

    /**
     * Excel wide sheet: NAME | DATE(IN/OUT) | DATE(IN/OUT) | …
     *
     * @param  array{dates:list,people:list}|array<int,array<string,mixed>>  $matrixOrRows
     */
    public function exportRangeExcel(array $matrixOrRows, string $from, string $to): string
    {
        $matrix = $this->normalizeToMatrix($matrixOrRows, $from, $to);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Attendance');

        $dates = $matrix['dates'];
        $people = $matrix['people'];
        $lastColIndex = 1 + (count($dates) * 2); // A=1, then 2 cols per date
        $lastCol = Coordinate::stringFromColumnIndex($lastColIndex);

        // Row 1: NAME + merged date headers
        $sheet->setCellValue('A1', 'NAME');
        $sheet->mergeCells('A1:A2');
        $sheet->getStyle('A1')->getFont()->setBold(true);
        $sheet->getStyle('A1')->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER)
            ->setVertical(Alignment::VERTICAL_CENTER);

        $colIndex = 2;
        foreach ($dates as $day) {
            $start = Coordinate::stringFromColumnIndex($colIndex);
            $end = Coordinate::stringFromColumnIndex($colIndex + 1);
            $sheet->mergeCells("{$start}1:{$end}1");
            $sheet->setCellValue("{$start}1", $day['label']);
            $sheet->getStyle("{$start}1")->getFont()->setBold(true);
            $sheet->getStyle("{$start}1")->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                ->setVertical(Alignment::VERTICAL_CENTER);

            $sheet->setCellValue("{$start}2", 'IN');
            $sheet->setCellValue("{$end}2", 'OUT');
            $sheet->getStyle("{$start}2:{$end}2")->getFont()->setBold(true);
            $sheet->getStyle("{$start}2:{$end}2")->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_CENTER);

            $colIndex += 2;
        }

        // Data: one row per personnel
        $rowNum = 3;
        foreach ($people as $person) {
            $sheet->setCellValue('A'.$rowNum, $person['name']);
            $colIndex = 2;
            foreach ($dates as $day) {
                $cell = $person['days'][$day['date']] ?? ['time_in' => null, 'time_out' => null];
                $inCol = Coordinate::stringFromColumnIndex($colIndex);
                $outCol = Coordinate::stringFromColumnIndex($colIndex + 1);
                $sheet->setCellValue($inCol.$rowNum, $cell['time_in'] ?? '');
                $sheet->setCellValue($outCol.$rowNum, $cell['time_out'] ?? '');
                $colIndex += 2;
            }
            $rowNum++;
        }

        $lastDataRow = max(2, $rowNum - 1);

        $sheet->getColumnDimension('A')->setWidth(28);
        for ($i = 2; $i <= $lastColIndex; $i++) {
            $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($i))->setWidth(11);
        }

        $borderRange = 'A1:'.$lastCol.$lastDataRow;
        $sheet->getStyle($borderRange)->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
        ]);

        if ($lastDataRow >= 3) {
            $sheet->getStyle('B3:'.$lastCol.$lastDataRow)
                ->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_LEFT);
        }

        $outputDir = storage_path('app/exports');
        if (! is_dir($outputDir)) {
            mkdir($outputDir, 0755, true);
        }

        $path = $outputDir.'/report_'.$from.'_'.$to.'.xlsx';
        (new Xlsx($spreadsheet))->save($path);

        return $path;
    }

    /**
     * @param  array{dates:list,people:list}|array<int,array<string,mixed>>  $matrixOrRows
     * @return array{dates:list,people:list}
     */
    protected function normalizeToMatrix(array $matrixOrRows, string $from, string $to): array
    {
        if (isset($matrixOrRows['dates'], $matrixOrRows['people'])) {
            return $matrixOrRows;
        }

        return $this->buildWideAttendanceMatrix($from, $to);
    }

    /**
     * Compact time like the reference sheet: 7:50AM (no space).
     */
    public function formatReportTime(mixed $time): ?string
    {
        if ($time === null || $time === '') {
            return null;
        }

        try {
            return Carbon::parse((string) $time)->format('g:iA');
        } catch (\Throwable) {
            return (string) $time;
        }
    }
}
