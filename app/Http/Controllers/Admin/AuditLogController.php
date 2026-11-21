<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    public function index(Request $request): View
    {
        $filter = $request->query('filter') === 'email' ? 'email' : 'all';

        $logs = AuditLog::when($filter === 'email', fn ($q) => $q->where('action', 'like', 'email.%'))
            ->orderByDesc('created_at')
            ->limit(200)
            ->get();

        $emailStats = [
            'sent' => AuditLog::where('action', 'email.sent')->count(),
            'failed' => AuditLog::where('action', 'email.failed')->count(),
            'skipped' => AuditLog::where('action', 'email.skipped')->count(),
        ];

        return view('admin.audit-log', compact('logs', 'filter', 'emailStats'));
    }
}
