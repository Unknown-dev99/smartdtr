<?php

namespace App\Services;

use App\Models\LocatorRequest;
use App\Models\Personnel;
use App\Models\StaffUser;
use Illuminate\Support\Facades\DB;

class LocatorRequestService
{
    public function __construct(
        protected DailyStatusService $dailyStatusService,
        protected NotificationService $notificationService,
        protected AuditLogService $auditLogService
    ) {}

    public function approve(LocatorRequest $request, int $adminId, ?string $notes = null): LocatorRequest
    {
        $approved = DB::transaction(function () use ($request, $adminId, $notes) {
            $request->update([
                'status' => 'approved',
                'reviewed_by' => $adminId,
                'reviewed_at' => now(),
                'admin_notes' => $notes,
                'actual_time_out' => $request->actual_time_out ?? $request->time_out,
            ]);

            $personnelId = $this->resolvePersonnelId($request);

            if ($personnelId) {
                if (! $request->personnel_id) {
                    $request->update(['personnel_id' => $personnelId]);
                }

                foreach ($request->fresh()->inclusiveDateStrings() as $date) {
                    $this->dailyStatusService->computeAndPersist($personnelId, $date);
                }
            }

            $this->auditLogService->log('locator.approved', "Approved locator slip {$request->slipNumber()}", $request);

            return $request->fresh(['staffUser', 'reviewer']);
        });

        if ($approved->staffUser) {
            $this->notificationService->notifyStaffLetterDecision(
                $approved->staffUser,
                'Locator Slip',
                'approved',
                "Your locator slip for {$approved->inclusiveDatesLabel()} to {$approved->destination} has been approved by the school administration.",
                route('staff.locator'),
                $approved->admin_notes
            );
        }

        return $approved;
    }

    public function reject(LocatorRequest $request, int $adminId, ?string $notes = null): LocatorRequest
    {
        $request->update([
            'status' => 'rejected',
            'reviewed_by' => $adminId,
            'reviewed_at' => now(),
            'admin_notes' => $notes,
        ]);

        $this->auditLogService->log('locator.rejected', "Rejected locator slip {$request->slipNumber()}", $request);
        if ($request->staffUser) {
            $this->notificationService->notifyStaffLetterDecision(
                $request->staffUser,
                'Locator Slip',
                'rejected',
                "Your locator slip for {$request->inclusiveDatesLabel()} to {$request->destination} was not approved.",
                route('staff.locator'),
                $notes
            );
        }

        return $request->fresh(['staffUser', 'reviewer']);
    }

    protected function resolvePersonnelId(LocatorRequest $request): ?int
    {
        if ($request->personnel_id) {
            return $request->personnel_id;
        }

        $staff = $request->staffUser;
        if ($staff?->personnel_id) {
            return $staff->personnel_id;
        }

        if (! $staff) {
            return null;
        }

        $personnel = Personnel::create([
            'name' => $staff->name,
            'role' => $staff->roleLabel(),
            'employee_number' => $staff->employee_number ?: StaffUser::nextEmployeeNumber(),
        ]);

        $staff->update([
            'personnel_id' => $personnel->id,
            'employee_number' => $personnel->employee_number,
        ]);

        return $personnel->id;
    }
}
