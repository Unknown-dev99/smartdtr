<?php

namespace App\Http\Middleware;

use App\Models\AdminUser;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckAdminPermission
{
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        /** @var AdminUser|null $admin */
        $admin = auth('admin')->user();

        if (! $admin || ! $admin->hasPermission($permission)) {
            abort(403, 'You do not have permission to access this page.');
        }

        return $next($request);
    }
}
