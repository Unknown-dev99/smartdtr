<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminAuthenticate
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! auth('admin')->check()) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json(['error' => 'Unauthenticated'], 401);
            }

            return redirect()->route('login');
        }

        return $next($request);
    }
}
