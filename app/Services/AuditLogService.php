<?php

namespace App\Services;

use App\Models\AdminUser;
use App\Models\AuditLog;
use App\Models\StaffUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class AuditLogService
{
    public function log(
        string $action,
        ?string $description = null,
        ?Model $subject = null,
        ?array $oldValues = null,
        ?array $newValues = null
    ): AuditLog {
        [$actorType, $actorId, $actorName] = $this->resolveActor();

        return AuditLog::create([
            'actor_type' => $actorType,
            'actor_id' => $actorId,
            'actor_name' => $actorName,
            'action' => $action,
            'subject_type' => $subject ? class_basename($subject) : null,
            'subject_id' => $subject?->getKey(),
            'description' => $description,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => request()->ip(),
            'created_at' => now(),
        ]);
    }

    /**
     * @return array{0:string,1:?int,2:?string}
     */
    protected function resolveActor(): array
    {
        if ($admin = Auth::guard('admin')->user()) {
            /** @var AdminUser $admin */
            return ['admin', $admin->id, $admin->username];
        }

        if ($staff = Auth::guard('staff')->user()) {
            /** @var StaffUser $staff */
            return ['staff', $staff->id, $staff->name];
        }

        return ['system', null, 'System'];
    }
}
