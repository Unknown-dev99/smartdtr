<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Personnel;
use App\Services\AttendanceService;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AttendanceCorrectionController extends Controller
{
    public function __construct(
        protected AttendanceService $attendanceService,
        protected AuditLogService $auditLog
    ) {}

    public function index(Request $request): View
    {
        $date = $request->input('date', now()->toDateString());
        $personnel = Personnel::active()->orderBy('name')->get();
        $selectedId = $request->input('personnel_id');
        $attendance = null;

        if ($selectedId) {
            $attendance = Attendance::where('personnel_id', $selectedId)->whereDate('date', $date)->first();
        }

        return view('admin.attendance-correction', compact('date', 'personnel', 'selectedId', 'attendance'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'personnel_id' => 'required|exists:personnel,id',
            'date' => 'required|date',
            'time_in' => 'nullable|date_format:H:i',
            'time_out' => 'nullable|date_format:H:i',
            'reason' => 'required|string|max:500',
        ]);

        $attendance = Attendance::where('personnel_id', $validated['personnel_id'])
            ->whereDate('date', $validated['date'])
            ->first();

        $old = $attendance ? $attendance->only(['time_in', 'time_out', 'status']) : null;

        $result = $this->attendanceService->applyManualCorrection(
            (int) $validated['personnel_id'],
            $validated['date'],
            $validated['time_in'] ?: null,
            $validated['time_out'] ?: null
        );

        $attendance = Attendance::where('personnel_id', $validated['personnel_id'])
            ->whereDate('date', $validated['date'])
            ->first();

        $this->auditLog->log(
            'attendance.corrected',
            'Manual correction: '.$validated['reason'],
            $attendance,
            $old,
            array_merge($attendance?->only(['time_in', 'time_out', 'status']) ?? [], ['reason' => $validated['reason']])
        );

        return redirect()->route('admin.attendance.correction', [
            'date' => $validated['date'],
            'personnel_id' => $validated['personnel_id'],
        ])->with('success', 'Attendance corrected and saved to DTR. Status: '.$result['status']);
    }
}
