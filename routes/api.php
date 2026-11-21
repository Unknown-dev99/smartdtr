<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Api\Admin\LeaveController as ApiLeaveController;
use App\Http\Controllers\Api\Admin\PersonnelController as ApiPersonnelController;
use App\Http\Controllers\Api\Admin\ReportController as ApiReportController;
use App\Http\Controllers\Api\Admin\ScheduleController as ApiScheduleController;
use App\Http\Controllers\Api\AttendanceScanController;
use App\Http\Controllers\Api\AuthController as ApiAuthController;
use App\Http\Controllers\Api\DeviceKioskController;
use App\Http\Middleware\AdminAuthenticate;
use Illuminate\Support\Facades\Route;

Route::post('/attendance/scan', [AttendanceScanController::class, 'store'])
    ->middleware('throttle:60,1');

Route::get('/device/ping', [DeviceKioskController::class, 'ping'])
    ->middleware('throttle:300,1');
Route::post('/device/heartbeat', [DeviceKioskController::class, 'heartbeat'])
    ->middleware('throttle:30,1');
Route::get('/device/roster', [DeviceKioskController::class, 'roster'])
    ->middleware('throttle:30,1');
Route::get('/device/faces', [DeviceKioskController::class, 'faces'])
    ->middleware('throttle:30,1');
Route::get('/device/faces/{id}', [DeviceKioskController::class, 'faceFile'])
    ->middleware('throttle:60,1')
    ->whereNumber('id');

Route::prefix('auth')->group(function () {
    Route::post('/login', [ApiAuthController::class, 'login']);
    Route::post('/logout', [ApiAuthController::class, 'logout'])->middleware('auth:admin');
    Route::get('/me', [ApiAuthController::class, 'me'])->middleware('auth:admin');
});

Route::prefix('admin')->middleware(['auth:admin'])->group(function () {
    Route::get('/personnel', [ApiPersonnelController::class, 'index']);
    Route::post('/personnel', [ApiPersonnelController::class, 'store']);

    Route::post('/leave', [ApiLeaveController::class, 'store']);
    Route::post('/schedule', [ApiScheduleController::class, 'store']);

    Route::get('/attendance', [ApiReportController::class, 'attendance']);
    Route::get('/status', [ApiReportController::class, 'status']);

    Route::get('/reports/daily', [ApiReportController::class, 'daily']);
    Route::get('/reports/weekly', [ApiReportController::class, 'weekly']);
    Route::get('/reports/dtr/pdf', [ApiReportController::class, 'dtrPdf']);
    Route::get('/reports/export/excel', [ApiReportController::class, 'exportExcel']);
    Route::get('/reports/export/pdf', [ApiReportController::class, 'exportPdf']);
});

Route::get('/admin/reports.php', function (\Illuminate\Http\Request $request) {
    $params = http_build_query([
        'personnel_id' => $request->query('personnel_id'),
        'month' => $request->query('month'),
    ]);

    return redirect('/api/admin/reports/dtr/pdf?'.$params);
});
