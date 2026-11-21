<?php

if (! function_exists('statusBadgeClass')) {
    function statusBadgeClass(?string $status): string
    {
        return match ($status) {
            'Present' => 'badge-present',
            'Late' => 'badge-late',
            'Absent' => 'badge-absent',
            'On Leave' => 'badge-leave',
            'Official Travel' => 'badge-travel',
            'Official Business' => 'badge-travel',
            'Off-duty' => 'badge-offduty',
            default => 'badge-offduty',
        };
    }
}

if (! function_exists('adminCan')) {
    function adminCan(string $permission): bool
    {
        /** @var \App\Models\AdminUser|null $admin */
        $admin = auth('admin')->user();

        return $admin && $admin->hasPermission($permission);
    }
}
