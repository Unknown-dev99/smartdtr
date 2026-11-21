<?php

namespace App\Services;

use App\Models\TravelOrder;

class TravelOrderService
{
    public function __construct(
        protected NotificationService $notificationService,
        protected AuditLogService $auditLogService
    ) {}

    public function approve(TravelOrder $order, int $adminId, ?string $notes = null): TravelOrder
    {
        $order->update([
            'status' => 'approved',
            'reviewed_by' => $adminId,
            'reviewed_at' => now(),
            'admin_notes' => $notes,
        ]);

        $this->auditLogService->log('travel_order.approved', "Approved travel order {$order->orderNumber()}", $order);

        if ($order->staffUser) {
            $this->notificationService->notifyStaffLetterDecision(
                $order->staffUser,
                'Travel Order',
                'approved',
                "Your travel order {$order->orderNumber()} for {$order->inclusiveDatesLabel()} ({$order->destination}) has been approved. Open Travel Order and use Print to download your copy.",
                route('staff.travel-orders'),
                $order->admin_notes
            );
        }

        return $order->fresh(['staffUser', 'reviewer']);
    }

    public function reject(TravelOrder $order, int $adminId, ?string $notes = null): TravelOrder
    {
        $order->update([
            'status' => 'rejected',
            'reviewed_by' => $adminId,
            'reviewed_at' => now(),
            'admin_notes' => $notes,
        ]);

        $this->auditLogService->log('travel_order.rejected', "Rejected travel order {$order->orderNumber()}", $order);

        if ($order->staffUser) {
            $this->notificationService->notifyStaffLetterDecision(
                $order->staffUser,
                'Travel Order',
                'rejected',
                "Your travel order {$order->orderNumber()} for {$order->inclusiveDatesLabel()} ({$order->destination}) was not approved.",
                route('staff.travel-orders'),
                $notes
            );
        }

        return $order->fresh(['staffUser', 'reviewer']);
    }
}
