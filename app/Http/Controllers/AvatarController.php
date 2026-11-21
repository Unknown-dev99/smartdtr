<?php

namespace App\Http\Controllers;

use App\Services\AuditLogService;
use App\Services\AvatarService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AvatarController extends Controller
{
    public function __construct(
        protected AvatarService $avatarService,
        protected AuditLogService $auditLog
    ) {}

    public function upload(Request $request): RedirectResponse
    {
        $guard = $this->resolveGuard($request);
        $user = auth($guard)->user();

        $request->validate([
            'avatar' => 'required|image|mimes:jpeg,jpg,png,webp|max:2048',
        ]);

        $this->avatarService->store($user, $guard, $request->file('avatar'));
        $this->auditLog->log('avatar.uploaded', 'Profile avatar updated', $user);

        return back()->with('success', 'Profile photo updated.');
    }

    public function remove(Request $request): RedirectResponse
    {
        $guard = $this->resolveGuard($request);
        $user = auth($guard)->user();

        $this->avatarService->remove($user);
        $this->auditLog->log('avatar.removed', 'Profile avatar removed', $user);

        return back()->with('success', 'Profile photo removed.');
    }

    protected function resolveGuard(Request $request): string
    {
        return str_starts_with($request->path(), 'staff') ? 'staff' : 'admin';
    }
}
