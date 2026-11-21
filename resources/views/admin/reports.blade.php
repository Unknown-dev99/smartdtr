@extends('layouts.admin')

@section('title', 'Reports - SMARTDTR')

@section('content')
<x-page-header title="Reports" subtitle="School attendance report, plus separate analytics you can print as a graph or a table." />

<div class="grid lg:grid-cols-3 gap-6 mb-6">
    <div class="app-card lg:col-span-1">
        <div class="app-card-header">
            <h2 class="app-card-title">Attendance sheet (PDF)</h2>
        </div>
        <div class="app-card-body">
            <form method="GET" action="{{ route('admin.reports.pdf') }}" class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div class="form-group">
                        <label class="form-label">From</label>
                        <input type="date" name="from" required class="form-input">
                    </div>
                    <div class="form-group">
                        <label class="form-label">To</label>
                        <input type="date" name="to" required class="form-input">
                    </div>
                </div>
                <button type="submit" class="btn-danger w-full">Export PDF</button>
            </form>
        </div>
    </div>

    <div class="app-card lg:col-span-2">
        <div class="app-card-header">
            <h2 class="app-card-title">Office analytics</h2>
        </div>
        <div class="app-card-body">
            <form method="GET" action="{{ route('admin.reports') }}" class="flex flex-wrap items-end gap-4">
                <div class="form-group">
                    <label class="form-label">From</label>
                    <input type="date" name="from" value="{{ $from }}" required class="form-input">
                </div>
                <div class="form-group">
                    <label class="form-label">To</label>
                    <input type="date" name="to" value="{{ $to }}" required class="form-input">
                </div>
                <button type="submit" class="btn-primary">Load analytics</button>
            </form>
            <p class="text-xs text-slate-500 mt-3">Counts are person-days from the same attendance statuses used on the school report: Present, Late, Absent, On Leave, Official Travel, Official Business, and Off-duty. Each analytic prints on its own page.</p>
        </div>
    </div>
</div>

@php
    $printUrl = function (string $report, string $format) use ($from, $to) {
        return route('admin.reports.analytics', compact('from', 'to') + ['report' => $report, 'format' => $format]);
    };
@endphp

<div class="grid lg:grid-cols-2 gap-6">
    <div class="app-card">
        <div class="app-card-header flex flex-wrap items-center justify-between gap-2">
            <h2 class="app-card-title">Attendance summary</h2>
            <div class="flex gap-2">
                <a href="{{ $printUrl('summary', 'graph') }}" target="_blank" class="btn-secondary btn-sm">Print graph</a>
                <a href="{{ $printUrl('summary', 'table') }}" target="_blank" class="btn-secondary btn-sm">Print table</a>
            </div>
        </div>
        <div class="app-card-body"><canvas id="summaryChart" height="180"></canvas></div>
    </div>
    <div class="app-card">
        <div class="app-card-header flex flex-wrap items-center justify-between gap-2">
            <h2 class="app-card-title">Daily attendance trend</h2>
            <div class="flex gap-2">
                <a href="{{ $printUrl('trend', 'graph') }}" target="_blank" class="btn-secondary btn-sm">Print graph</a>
                <a href="{{ $printUrl('trend', 'table') }}" target="_blank" class="btn-secondary btn-sm">Print table</a>
            </div>
        </div>
        <div class="app-card-body"><canvas id="trendChart" height="180"></canvas></div>
    </div>
    <div class="app-card">
        <div class="app-card-header flex flex-wrap items-center justify-between gap-2">
            <h2 class="app-card-title">Personnel attendance</h2>
            <div class="flex gap-2">
                <a href="{{ $printUrl('personnel', 'graph') }}" target="_blank" class="btn-secondary btn-sm">Print graph</a>
                <a href="{{ $printUrl('personnel', 'table') }}" target="_blank" class="btn-secondary btn-sm">Print table</a>
            </div>
        </div>
        <div class="app-card-body"><canvas id="personnelChart" height="180"></canvas></div>
    </div>
    <div class="app-card">
        <div class="app-card-header flex flex-wrap items-center justify-between gap-2">
            <h2 class="app-card-title">Teaching and non-teaching</h2>
            <div class="flex gap-2">
                <a href="{{ $printUrl('groups', 'graph') }}" target="_blank" class="btn-secondary btn-sm">Print graph</a>
                <a href="{{ $printUrl('groups', 'table') }}" target="_blank" class="btn-secondary btn-sm">Print table</a>
            </div>
        </div>
        <div class="app-card-body"><canvas id="groupChart" height="180"></canvas></div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script type="application/json" id="office-analytics">{!! json_encode($analytics) !!}</script>
<script>
(function () {
    const data = JSON.parse(document.getElementById('office-analytics').textContent);
    const statuses = data.statuses;
    const colors = statuses.map(status => data.colors[status]);
    const legend = { position: 'bottom' };
    const axis = { y: { beginAtZero: true, ticks: { stepSize: 1 } } };

    new Chart(document.getElementById('summaryChart'), {
        type: 'doughnut',
        data: {
            labels: statuses,
            datasets: [{ data: statuses.map(status => data.summary[status] || 0), backgroundColor: colors }]
        },
        options: { responsive: true, plugins: { legend } }
    });

    new Chart(document.getElementById('trendChart'), {
        type: 'bar',
        data: {
            labels: data.trend.map(day => day.label),
            datasets: statuses.map(status => ({
                label: status,
                data: data.trend.map(day => day.counts[status] || 0),
                backgroundColor: data.colors[status]
            }))
        },
        options: { responsive: true, plugins: { legend }, scales: { x: { stacked: true }, y: { stacked: true, beginAtZero: true, ticks: { stepSize: 1 } } } }
    });

    const ranked = data.personnel.filter(person => person.late_absent > 0).sort((a, b) => b.late_absent - a.late_absent).slice(0, 12);
    new Chart(document.getElementById('personnelChart'), {
        type: 'bar',
        data: {
            labels: ranked.map(person => person.name),
            datasets: [
                { label: 'Late', data: ranked.map(person => person.counts.Late || 0), backgroundColor: data.colors.Late },
                { label: 'Absent', data: ranked.map(person => person.counts.Absent || 0), backgroundColor: data.colors.Absent }
            ]
        },
        options: { indexAxis: 'y', responsive: true, plugins: { legend }, scales: axis }
    });

    new Chart(document.getElementById('groupChart'), {
        type: 'bar',
        data: {
            labels: statuses,
            datasets: Object.keys(data.groups).map((group, index) => ({
                label: group,
                data: statuses.map(status => data.groups[group][status] || 0),
                backgroundColor: index === 0 ? '#0D47A1' : '#F9A825'
            }))
        },
        options: { responsive: true, plugins: { legend }, scales: axis }
    });
})();
</script>
@endpush
