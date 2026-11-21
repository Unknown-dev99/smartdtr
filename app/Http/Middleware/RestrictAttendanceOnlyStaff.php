<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RestrictAttendanceOnlyStaff
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var \App\Models\StaffUser|null $staff */
        $staff = auth('staff')->user();

        if ($staff?->isAttendanceOnly()) {
            abort(403, 'Cleaning staff accounts only have access to attendance.');
        }

        return $next($request);
    }
}
