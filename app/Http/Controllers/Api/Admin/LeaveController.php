<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\LeaveRecord;
use App\Services\DailyStatusService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LeaveController extends Controller
{
    public function __construct(
        protected DailyStatusService $dailyStatusService
    ) {}

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'personnel_id' => 'required|exists:personnel,id',
            'date' => 'required|date',
            'leave_type' => 'required|in:Leave,Travel',
            'reason' => 'nullable|string|max:500',
        ]);

        $record = LeaveRecord::updateOrCreate(
            [
                'personnel_id' => $validated['personnel_id'],
                'date' => $validated['date'],
                'leave_type' => $validated['leave_type'],
            ],
            [
                'reason' => $validated['reason'] ?? null,
                'created_by' => Auth::guard('admin')->id(),
            ]
        );

        $status = $this->dailyStatusService->computeAndPersist(
            (int) $validated['personnel_id'],
            $validated['date']
        );

        return response()->json([
            'message' => 'Leave record saved',
            'data' => $record,
            'status' => $status,
        ], 201);
    }
}
