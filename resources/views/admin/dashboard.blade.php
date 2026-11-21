@extends('layouts.admin')

@section('title', 'Dashboard - SMART-DTR')

@section('content')
<x-page-header title="Daily Overview" subtitle="Personnel attendance status for selected date" />

{{-- Pi Device Monitor --}}
<div class="grid md:grid-cols-3 gap-4 mb-6">
    <div id="kiosk-card" class="stat-card {{ $device->isOnline() ? 'stat-card-green' : 'stat-card-red' }} md:col-span-1">
        <p class="stat-label">Raspberry Pi Kiosk</p>
        <p id="kiosk-status" class="text-xl font-bold text-slate-900 mt-1">{{ $device->statusLabel() }}</p>
        <p id="kiosk-last-seen" class="text-xs text-slate-400 mt-1">Last seen: {{ $device->lastSeenHuman() }}</p>
        <p id="kiosk-last-scan" class="text-xs text-slate-400 {{ $device->last_scan_at ? '' : 'hidden' }}">
            Last scan: {{ $device->last_scan_at?->diffForHumans() }}
        </p>
        <p class="text-xs text-slate-400 mt-1">Online = Pi is sending heartbeats on this Wi‑Fi (not internet).</p>
    </div>
    <div class="stat-card stat-card-blue">
        <p class="stat-label">Scans Today</p>
        <p id="kiosk-scans" class="stat-value">{{ $device->scan_count_date?->isToday() ? $device->scan_count_today : 0 }}</p>
    </div>
    <div class="stat-card stat-card-purple">
        <p class="stat-label">Your Role</p>
        <p class="text-lg font-bold text-slate-900 mt-1">{{ auth('admin')->user()->roleLabel() }}</p>
    </div>
</div>

<form method="GET" class="filter-bar">
    <div class="form-group">
        <label class="form-label">Date</label>
        <input type="date" name="date" value="{{ $date }}" class="form-input w-auto">
    </div>
    <button type="submit" class="btn-primary">Load</button>
</form>

<div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-7 gap-4 mb-6">
    <div class="stat-card stat-card-green"><p class="stat-label">Present</p><p class="stat-value">{{ $counts['Present'] ?? 0 }}</p></div>
    <div class="stat-card stat-card-orange"><p class="stat-label">Late</p><p class="stat-value">{{ $counts['Late'] ?? 0 }}</p></div>
    <div class="stat-card stat-card-red"><p class="stat-label">Absent</p><p class="stat-value">{{ $counts['Absent'] ?? 0 }}</p></div>
    <div class="stat-card stat-card-purple"><p class="stat-label">On Leave</p><p class="stat-value">{{ $counts['On Leave'] ?? 0 }}</p></div>
    <div class="stat-card stat-card-blue"><p class="stat-label">Official Travel</p><p class="stat-value">{{ $counts['Official Travel'] ?? 0 }}</p></div>
    <div class="stat-card stat-card-blue"><p class="stat-label">Official Business</p><p class="stat-value">{{ $counts['Official Business'] ?? 0 }}</p></div>
    <div class="stat-card"><p class="stat-label">Off-duty</p><p class="stat-value">{{ $counts['Off-duty'] ?? 0 }}</p></div>
</div>

{{-- Charts --}}
<div class="grid lg:grid-cols-2 gap-6 mb-6">
    <div class="app-card">
        <div class="app-card-header"><h2 class="app-card-title">14-Day Attendance Trend</h2></div>
        <div class="app-card-body"><canvas id="trendChart" height="200"></canvas></div>
    </div>
    <div class="app-card">
        <div class="app-card-header"><h2 class="app-card-title">This Month Summary</h2></div>
        <div class="app-card-body"><canvas id="monthChart" height="200"></canvas></div>
    </div>
</div>

<div class="app-card">
    <div class="table-wrap">
        <table class="app-table">
            <thead>
                <tr><th>ID</th><th>Name</th><th>Role</th><th>Status</th><th>Time In</th><th>Time Out</th></tr>
            </thead>
            <tbody>
                @forelse($records as $record)
                <tr>
                    <td class="font-mono text-slate-400">#{{ $record['id'] }}</td>
                    <td class="font-semibold text-slate-900">{{ $record['name'] }}</td>
                    <td>{{ $record['role'] }}</td>
                    <td><span class="badge {{ statusBadgeClass($record['status']) }}">{{ $record['status'] }}</span></td>
                    <td>{{ $record['time_in'] ? date('g:i A', strtotime($record['time_in'])) : '—' }}</td>
                    <td>{{ $record['time_out'] ? date('g:i A', strtotime($record['time_out'])) : '—' }}</td>
                </tr>
                @empty
                <tr><td colspan="6" class="empty-state-text py-8 text-center">No personnel found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script type="application/json" id="dashboard-chart-data">{!! json_encode([
    'trend' => $trend,
    'monthSummary' => $monthSummary,
]) !!}</script>
<script>
(function () {
    const payload = JSON.parse(document.getElementById('dashboard-chart-data').textContent);
    const trend = payload.trend ?? [];
    const monthSummary = payload.monthSummary ?? {};

    new Chart(document.getElementById('trendChart'), {
        type: 'line',
        data: {
            labels: trend.map(d => d.label),
            datasets: [
                { label: 'Present', data: trend.map(d => d.present), borderColor: '#2E7D32', backgroundColor: 'rgba(46,125,50,0.1)', fill: true, tension: 0.3 },
                { label: 'Late', data: trend.map(d => d.late), borderColor: '#FBC02D', backgroundColor: 'rgba(251,192,45,0.15)', fill: true, tension: 0.3 },
                { label: 'Absent', data: trend.map(d => d.absent), borderColor: '#c62828', backgroundColor: 'rgba(198,40,40,0.1)', fill: true, tension: 0.3 },
            ]
        },
        options: { responsive: true, plugins: { legend: { position: 'bottom' } }, scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } } }
    });

    new Chart(document.getElementById('monthChart'), {
        type: 'doughnut',
        data: {
            labels: ['Present', 'Late', 'Absent', 'On Leave'],
            datasets: [{
                data: [
                    monthSummary.Present ?? 0,
                    monthSummary.Late ?? 0,
                    monthSummary.Absent ?? 0,
                    monthSummary['On Leave'] ?? 0,
                ],
                backgroundColor: ['#2E7D32', '#FBC02D', '#c62828', '#0D47A1'],
            }]
        },
        options: { responsive: true, plugins: { legend: { position: 'bottom' } } }
    });
})();

(function () {
    const card = document.getElementById('kiosk-card');
    const statusEl = document.getElementById('kiosk-status');
    const seenEl = document.getElementById('kiosk-last-seen');
    const scanEl = document.getElementById('kiosk-last-scan');
    const scansEl = document.getElementById('kiosk-scans');
    const url = @json(route('admin.dashboard.kiosk-status'));

    if (!card || !statusEl) {
        return;
    }

    const paint = (data) => {
        statusEl.textContent = data.status;
        seenEl.textContent = 'Last seen: ' + data.last_seen;
        scansEl.textContent = data.scans_today;
        card.classList.toggle('stat-card-green', data.online);
        card.classList.toggle('stat-card-red', !data.online);
        if (data.last_scan) {
            scanEl.textContent = 'Last scan: ' + data.last_scan;
            scanEl.classList.remove('hidden');
        } else {
            scanEl.classList.add('hidden');
        }
    };

    const refresh = () => {
        fetch(url, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
            .then((res) => res.ok ? res.json() : Promise.reject())
            .then(paint)
            .catch(() => {});
    };

    refresh();
    setInterval(refresh, 5000);
})();
</script>
@endpush
