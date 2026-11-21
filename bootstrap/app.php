<?php

use App\Http\Middleware\CheckAdminPermission;
use App\Http\Middleware\RestrictAttendanceOnlyStaff;
use App\Http\Middleware\StaffAuthenticate;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'admin.permission' => CheckAdminPermission::class,
            'auth.staff' => StaffAuthenticate::class,
            'staff.full' => RestrictAttendanceOnlyStaff::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (\Illuminate\Session\TokenMismatchException $e, \Illuminate\Http\Request $request) {
            $message = 'This page expired before the face enrollment could be saved. Refresh and complete the three blink captures again.';

            if ($request->routeIs('admin.hr.faces.store')) {
                return redirect()
                    ->route('admin.hr.faces')
                    ->with('error', $message);
            }

            return redirect()->back()->with('error', $message);
        });
    })->create();
