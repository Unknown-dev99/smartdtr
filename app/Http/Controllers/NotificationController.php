<?php

namespace App\Http\Controllers;

use App\Models\AdminUser;
use App\Models\StaffUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function index(Request $request): View
    {
        [$guard, $user] = $this->notifiable();
        $notifications = $user->notifications()->latest()->limit(50)->get();

        return view('notifications.index', compact('notifications', 'guard'));
    }

    public function markRead(string $id): RedirectResponse
    {
        [, $user] = $this->notifiable();
        $notification = $user->notifications()->where('id', $id)->firstOrFail();
        $notification->markAsRead();

        $url = $notification->data['url'] ?? url()->previous();

        return redirect($url);
    }

    public function markAllRead(): RedirectResponse
    {
        [, $user] = $this->notifiable();
        $user->unreadNotifications->markAsRead();

        return back()->with('success', 'All notifications marked as read.');
    }

    /**
     * @return array{0:string,1:AdminUser|StaffUser}
     */
    private function notifiable(): array
    {
        if (auth('admin')->check()) {
            return ['admin', $this->adminUser()];
        }

        /** @var StaffUser $staff */
        $staff = auth('staff')->user();
        abort_unless($staff, 403);

        return ['staff', $staff];
    }
}
