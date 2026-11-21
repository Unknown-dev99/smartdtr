<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Holiday;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HolidayController extends Controller
{
    public function __construct(
        protected AuditLogService $auditLog
    ) {}

    public function index(): View
    {
        $holidays = Holiday::orderByDesc('date')->get();

        return view('admin.holidays', compact('holidays'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'date' => 'required|date|unique:holidays,date',
            'type' => 'required|in:holiday,suspension,non_working',
        ]);

        $holiday = Holiday::create([
            ...$validated,
            'created_by' => auth('admin')->id(),
        ]);

        $this->auditLog->log('holiday.created', "Added holiday: {$holiday->name}", $holiday);

        return back()->with('success', 'Holiday added.');
    }

    public function destroy(Holiday $holiday): RedirectResponse
    {
        $this->auditLog->log('holiday.deleted', "Removed holiday: {$holiday->name}", $holiday);
        $holiday->delete();

        return back()->with('success', 'Holiday removed.');
    }
}
