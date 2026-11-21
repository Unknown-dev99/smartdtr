<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LeaveBalance;
use App\Models\Personnel;
use App\Services\AuditLogService;
use App\Services\LeaveBalanceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PersonnelManageController extends Controller
{
    public function __construct(
        protected AuditLogService $auditLog,
        protected LeaveBalanceService $leaveBalanceService
    ) {}

    public function edit(Personnel $personnel): View
    {
        $balance = $this->leaveBalanceService->ensureBalance($personnel);

        return view('admin.personnel.edit', compact('personnel', 'balance'));
    }

    public function update(Request $request, Personnel $personnel): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'regex:/^[\p{L}]+(?:[ .\'\-][\p{L}]+)*$/u'],
            'role' => ['required', 'string', 'max:255', 'regex:/^[\p{L}0-9]+(?:[ .\/\-][\p{L}0-9]+)*$/u'],
            'employee_number' => ['nullable', 'regex:/^[0-9]+(?:-[0-9]+)*$/', 'max:50'],
            'regular_am_in' => ['nullable', 'regex:/^\d{1,2}:\d{2}(:\d{2})?$/'],
            'regular_am_out' => ['nullable', 'regex:/^\d{1,2}:\d{2}(:\d{2})?$/'],
            'regular_pm_in' => ['nullable', 'regex:/^\d{1,2}:\d{2}(:\d{2})?$/'],
            'regular_pm_out' => ['nullable', 'regex:/^\d{1,2}:\d{2}(:\d{2})?$/'],
            'saturday_in' => ['nullable', 'regex:/^\d{1,2}:\d{2}(:\d{2})?$/'],
            'saturday_out' => ['nullable', 'regex:/^\d{1,2}:\d{2}(:\d{2})?$/'],
            'vl_balance' => 'nullable|numeric|min:0',
            'sl_balance' => 'nullable|numeric|min:0',
        ], [
            'name.regex' => 'Name may contain letters only.',
            'role.regex' => 'Role may contain letters and numbers only.',
            'employee_number.regex' => 'Employee number may contain digits and hyphens (e.g. 00-001).',
        ]);

        $old = $personnel->only(['name', 'role', 'employee_number']);
        $personnel->update([
            'name' => $validated['name'],
            'role' => $validated['role'],
            'employee_number' => $validated['employee_number'] ?? null,
            ...Personnel::hoursFromRequest($request),
        ]);

        if ($request->filled('vl_balance') || $request->filled('sl_balance')) {
            $balance = $this->leaveBalanceService->ensureBalance($personnel);
            $balance->update([
                'vl_balance' => $request->input('vl_balance', $balance->vl_balance),
                'sl_balance' => $request->input('sl_balance', $balance->sl_balance),
            ]);
        }

        $this->auditLog->log('personnel.updated', "Updated personnel #{$personnel->id}", $personnel, $old, $personnel->fresh()->only(['name', 'role', 'employee_number']));

        return redirect()->route('admin.personnel')->with('success', 'Personnel updated.');
    }

    public function deactivate(Personnel $personnel): RedirectResponse
    {
        $personnel->update(['is_active' => false, 'deactivated_at' => now()]);
        $this->auditLog->log('personnel.deactivated', "Deactivated personnel #{$personnel->id}", $personnel);

        return back()->with('success', "{$personnel->name} has been deactivated.");
    }

    public function activate(Personnel $personnel): RedirectResponse
    {
        $personnel->update(['is_active' => true, 'deactivated_at' => null]);
        $this->auditLog->log('personnel.activated', "Reactivated personnel #{$personnel->id}", $personnel);

        return back()->with('success', "{$personnel->name} is active again.");
    }
}
