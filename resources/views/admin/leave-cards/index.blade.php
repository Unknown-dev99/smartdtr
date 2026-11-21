@extends('layouts.admin')

@section('title', 'Leave Card - SMARTDTR')

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-bold text-primary-900">Employee's Leave Card</h1>
    <p class="text-slate-500 text-sm">Administrative staff only. Each month earns 1.25 vacation leave and 1.25 sick leave, so twelve months earn 15 of each. The balance is the previous balance plus what was earned, minus absence with pay. Teaching staff are not listed here.</p>
</div>

<div class="app-card">
    <div class="overflow-x-auto">
        <table class="app-table">
            <thead>
                <tr>
                    <th>Employee</th>
                    <th>Position</th>
                    <th>VL balance</th>
                    <th>SL balance</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($people as $person)
                @php($credit = $balances[$person->id] ?? null)
                <tr>
                    <td class="font-medium">{{ $person->name }}</td>
                    <td>{{ $person->role }}</td>
                    <td>{{ $credit ? \App\Services\LeaveCardService::formatCredit($credit['vl']) : '—' }}</td>
                    <td>{{ $credit ? \App\Services\LeaveCardService::formatCredit($credit['sl']) : '—' }}</td>
                    <td><a href="{{ route('admin.leave-cards.show', $person) }}" class="btn-secondary text-xs">Open card</a></td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="text-slate-500">No administrative staff are on file.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
