<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\AttendanceLog;
use App\Models\Personnel;
use App\Services\DailyStatusService;
use App\Services\DtrExportService;
use App\Services\ReportExportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ReportController extends Controller
{
    public function __construct(
        protected ReportExportService $reportService,
        protected DailyStatusService $dailyStatusService,
        protected DtrExportService $dtrService
    ) {}

    public function attendance(Request $request): JsonResponse
    {
        $date = $request->validate(['date' => 'required|date'])['date'];

        $records = Attendance::with('personnel')
            ->whereDate('date', $date)
            ->get()
            ->map(fn ($a) => [
                'personnel_id' => $a->personnel_id,
                'name' => $a->personnel->name,
                'date' => $a->date->toDateString(),
                'time_in' => $a->time_in,
                'time_out' => $a->time_out,
                'status' => $a->status,
            ]);

        return response()->json(['date' => $date, 'data' => $records]);
    }

    public function status(Request $request): JsonResponse
    {
        $date = $request->validate(['date' => 'required|date'])['date'];
        $report = $this->reportService->buildDailyReport($date);

        return response()->json($report);
    }

    public function daily(Request $request): JsonResponse
    {
        $date = $request->validate(['date' => 'required|date'])['date'];
        $report = $this->reportService->buildDailyReport($date);

        $logs = AttendanceLog::with('personnel')
            ->whereDate('date', $date)
            ->orderBy('time')
            ->get()
            ->map(fn ($log) => [
                'personnel_id' => $log->personnel_id,
                'name' => $log->personnel->name,
                'time' => $log->time,
            ]);

        return response()->json(array_merge($report, ['logs' => $logs]));
    }

    public function weekly(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'from' => 'required|date',
            'to' => 'required|date|after_or_equal:from',
        ]);

        $rows = $this->reportService->buildRangeReport($validated['from'], $validated['to']);

        return response()->json([
            'from' => $validated['from'],
            'to' => $validated['to'],
            'data' => $rows,
        ]);
    }

    public function dtrExcel(Request $request): BinaryFileResponse|JsonResponse
    {
        $validated = $request->validate([
            'personnel_id' => 'required|exists:personnel,id',
            'month' => 'required|date_format:Y-m',
        ]);

        try {
            $personnel = Personnel::findOrFail($validated['personnel_id']);
            $path = $this->dtrService->generateExcel($personnel, $validated['month']);

            return response()->download($path)->deleteFileAfterSend(false);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function dtrPdf(Request $request)
    {
        $validated = $request->validate([
            'personnel_id' => 'required|exists:personnel,id',
            'month' => 'required|date_format:Y-m',
        ]);

        $personnel = Personnel::findOrFail($validated['personnel_id']);
        $path = $this->dtrService->generateOfficialPdf($personnel, $validated['month']);

        return response()->download($path, 'DTR_'.$personnel->name.'_'.$validated['month'].'.pdf');
    }

    public function exportExcel(Request $request): BinaryFileResponse|JsonResponse
    {
        $validated = $request->validate([
            'from' => 'required|date',
            'to' => 'required|date|after_or_equal:from',
        ]);

        try {
            $matrix = $this->reportService->buildWideAttendanceMatrix($validated['from'], $validated['to']);
            $path = $this->reportService->exportRangeExcel($matrix, $validated['from'], $validated['to']);

            return response()->download($path)->deleteFileAfterSend(false);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    public function exportPdf(Request $request)
    {
        $validated = $request->validate([
            'from' => 'required|date',
            'to' => 'required|date|after_or_equal:from',
        ]);

        try {
            $matrix = $this->reportService->buildWideAttendanceMatrix($validated['from'], $validated['to']);
            $layout = $this->reportService->buildPrintableAttendanceLayout(
                $matrix,
                $validated['from'],
                $validated['to']
            );
            $pdf = Pdf::loadView('reports.range-pdf', [
                'layout' => $layout,
                'from' => $validated['from'],
                'to' => $validated['to'],
            ])->setPaper('legal', 'landscape');

            return $pdf->download('report_'.$validated['from'].'_'.$validated['to'].'.pdf');
        } catch (\InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }
}
