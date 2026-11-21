<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Schedule;
use App\Models\ScheduleOverride;
use App\Services\DailyStatusService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ScheduleController extends Controller
{
    public function __construct(
        protected DailyStatusService $dailyStatusService
    ) {}

    public function store(Request $request): JsonResponse
    {
        if ($request->filled('date')) {
            return $this->storeOverride($request);
        }

        $validated = $request->validate([
            'personnel_id' => 'required|exists:personnel,id',
            'day_of_week' => 'required|integer|min:0|max:6',
            'start_time' => 'required|date_format:H:i:s',
            'end_time' => 'required|date_format:H:i:s|after:start_time',
            'subject' => 'required|string|max:255',
        ]);

        $schedule = Schedule::create($validated);

        return response()->json([
            'message' => 'Weekly schedule created',
            'data' => $schedule,
        ], 201);
    }

    protected function storeOverride(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'personnel_id' => 'required|exists:personnel,id',
            'date' => 'required|date',
            'override_type' => 'required|in:Class,Off-duty',
            'start_time' => 'nullable|required_if:override_type,Class|date_format:H:i:s',
            'end_time' => 'nullable|required_if:override_type,Class|date_format:H:i:s',
            'subject' => 'nullable|string|max:255',
        ]);

        $override = ScheduleOverride::updateOrCreate(
            ['personnel_id' => $validated['personnel_id'], 'date' => $validated['date']],
            [
                'override_type' => $validated['override_type'],
                'start_time' => $validated['start_time'] ?? null,
                'end_time' => $validated['end_time'] ?? null,
                'subject' => $validated['subject'] ?? null,
            ]
        );

        $status = $this->dailyStatusService->computeAndPersist(
            (int) $validated['personnel_id'],
            $validated['date']
        );

        return response()->json([
            'message' => 'Schedule override saved',
            'data' => $override,
            'status' => $status,
        ], 201);
    }
}
