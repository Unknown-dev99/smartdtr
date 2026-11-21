@props(['guard' => 'admin', 'variant' => 'dark'])

@php
    $user = auth($guard)->user();
    $unread = $user ? $user->unreadNotifications()->count() : 0;
    $route = $guard === 'staff' ? route('staff.notifications') : route('admin.notifications');
    $btnClass = $variant === 'light'
        ? 'topbar-icon-btn'
        : 'relative inline-flex items-center px-2 py-1 rounded-lg text-blue-100 hover:text-white hover:bg-white/10 transition-all';
@endphp

<a href="{{ $route }}" class="{{ $btnClass }} relative" title="Notifications">
    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/></svg>
    @if($unread > 0)
        <span class="topbar-notify-badge">{{ $unread > 9 ? '9+' : $unread }}</span>
    @endif
</a>
