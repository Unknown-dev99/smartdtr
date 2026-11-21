<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DeviceHeartbeat;
use App\Models\Personnel;
use App\Services\AttendanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AttendanceScanController extends Controller
{
    public function __construct(
        protected AttendanceService $attendanceService
    ) {}

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'personnel_id' => 'required|integer',
            'date' => 'nullable|date',
            'time' => 'nullable|date_format:H:i:s',
            'date_override' => 'nullable|date',
            'blink_detected' => 'required|boolean',
            'face_confidence' => 'required|numeric',
        ]);

        $result = $this->attendanceService->processScan($validated);

        if (! $result['success']) {
            return response()->json(['error' => $result['message']], $result['status'] ?? 422);
        }

        DeviceHeartbeat::recordScan($request->ip());

        $person = Personnel::find($validated['personnel_id']);
        $status = $result['data'] ?? [];

        return response()->json([
            'message' => $result['message'] ?? 'Scan recorded',
            'data' => array_merge($status, [
                'personnel_id' => (int) $validated['personnel_id'],
                'name' => $person?->name,
                'employee_number' => $person?->employee_number,
                'already_attended' => (bool) ($status['already_attended'] ?? false),
            ]),
        ]);
    }
}
