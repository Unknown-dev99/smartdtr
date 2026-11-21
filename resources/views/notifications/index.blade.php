@extends($guard === 'staff' ? 'layouts.staff' : 'layouts.admin')

@section('title', 'Notifications - SMARTDTR')

@section('content')
<x-page-header title="Notifications" subtitle="In-app alerts and system messages" />

<div class="app-card">
    <div class="app-card-header flex justify-between items-center">
        <h2 class="app-card-title">Your Notifications</h2>
        @if($notifications->whereNull('read_at')->count())
        <form method="POST" action="{{ $guard === 'staff' ? route('staff.notifications.read-all') : route('admin.notifications.read-all') }}">
            @csrf
            <button type="submit" class="btn-secondary btn-sm">Mark all read</button>
        </form>
        @endif
    </div>
    <div class="divide-y">
        @forelse($notifications as $notification)
        <div class="p-4 flex gap-4 {{ $notification->read_at ? 'opacity-70' : 'bg-primary-50/30' }}">
            <div class="flex-1">
                <p class="font-semibold text-slate-900">{{ $notification->data['title'] ?? 'Notification' }}</p>
                <p class="text-sm text-slate-500 mt-1">{{ $notification->data['message'] ?? '' }}</p>
                <p class="text-xs text-slate-400 mt-2">{{ $notification->created_at->diffForHumans() }}</p>
            </div>
            @if(!$notification->read_at)
            <form method="POST" action="{{ $guard === 'staff' ? route('staff.notifications.read', $notification->id) : route('admin.notifications.read', $notification->id) }}">
                @csrf
                <button type="submit" class="btn-primary btn-sm">Open</button>
            </form>
            @endif
        </div>
        @empty
        <p class="empty-state-text py-12 text-center">No notifications yet.</p>
        @endforelse
    </div>
</div>
@endsection
