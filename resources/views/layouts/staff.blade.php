@extends('layouts.app')

@section('sidebar')
<aside class="sidebar" :class="{ 'sidebar-open': sidebarOpen }">
    <div class="sidebar-brand">
        <a href="{{ route('staff.dashboard') }}" class="sidebar-brand-link">
            <x-logo variant="sidebar" class="sidebar-brand-logo" />
            <div>
                <div class="sidebar-brand-title">SMART-DTR</div>
                <div class="sidebar-brand-sub">Employee Portal</div>
            </div>
        </a>
    </div>

    <nav class="sidebar-nav">
        <p class="sidebar-section">Menu</p>
        <x-sidebar-link href="{{ route('staff.dashboard') }}" label="Dashboard" :active="request()->routeIs('staff.dashboard')">
            <x-slot:icon><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg></x-slot:icon>
        </x-sidebar-link>
        @php($staffUnread = auth('staff')->user()?->unreadNotifications()->count() ?? 0)
        <x-sidebar-link href="{{ route('staff.notifications') }}" label="Notifications" :badge="$staffUnread" badge-variant="danger" :active="request()->routeIs('staff.notifications*')" title="{{ $staffUnread ? $staffUnread.' unread notification'.($staffUnread === 1 ? '' : 's') : 'Notifications' }}">
            <x-slot:icon><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/></svg></x-slot:icon>
        </x-sidebar-link>
        @unless(auth('staff')->user()?->isAttendanceOnly())
        <x-sidebar-link href="{{ route('staff.leave') }}" label="Leave Requests" :active="request()->routeIs('staff.leave*')">
            <x-slot:icon><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg></x-slot:icon>
        </x-sidebar-link>
        <x-sidebar-link href="{{ route('staff.locator') }}" label="Locator Slip" :active="request()->routeIs('staff.locator*')">
            <x-slot:icon><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg></x-slot:icon>
        </x-sidebar-link>
        <x-sidebar-link href="{{ route('staff.travel-orders') }}" label="Travel Order" :active="request()->routeIs('staff.travel-orders*')">
            <x-slot:icon><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17.8 19.2 16 11l3.5-3.5C21 6 21.5 4 21 3c-1-.5-3 0-4.5 1.5L13 8 4.8 6.2c-.5-.1-.9.1-1.1.5l-.3.5c-.2.5-.1 1 .3 1.3L9 12l-2 3H4l-1 1 3 2 2 3 1-1v-3l3-2 3.5 5.3c.3.4.8.5 1.3.3l.5-.2c.4-.3.6-.7.5-1.2z"/></svg></x-slot:icon>
        </x-sidebar-link>
        <x-sidebar-link href="{{ route('staff.files201.index') }}" label="My 201 File" :active="request()->routeIs('staff.files201*')">
            <x-slot:icon><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg></x-slot:icon>
        </x-sidebar-link>
        @endunless
        <x-sidebar-link href="{{ route('staff.attendance') }}" label="My Attendance" :active="request()->routeIs('staff.attendance')">
            <x-slot:icon><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg></x-slot:icon>
        </x-sidebar-link>
        <x-sidebar-link href="{{ route('staff.faces') }}" label="Register My Face" :active="request()->routeIs('staff.faces*')">
            <x-slot:icon><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg></x-slot:icon>
        </x-sidebar-link>
        @unless(auth('staff')->user()?->isAttendanceOnly())
        <x-sidebar-link href="{{ route('staff.schedule') }}" label="My Schedule" :active="request()->routeIs('staff.schedule*')">
            <x-slot:icon><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg></x-slot:icon>
        </x-sidebar-link>
        @endunless
        <x-sidebar-link href="{{ route('staff.profile') }}" label="My Profile" :active="request()->routeIs('staff.profile')">
            <x-slot:icon><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg></x-slot:icon>
        </x-sidebar-link>
    </nav>

    <div class="sidebar-footer">
        <a href="{{ route('staff.profile') }}" class="sidebar-user">
            <x-user-avatar :user="auth('staff')->user()" size="sm" />
            <div class="sidebar-user-info">
                <span class="sidebar-user-name">{{ auth('staff')->user()->name }}</span>
                <span class="sidebar-user-role">{{ auth('staff')->user()->roleLabel() }}</span>
            </div>
        </a>
        <div class="sidebar-footer-links">
            <a href="{{ route('staff.password') }}" class="sidebar-footer-link">Change Password</a>
        </div>
        <form method="POST" action="{{ route('staff.logout') }}" data-confirm="Are you sure you want to log out?" data-confirm-button="Log Out">
            @csrf
            <button type="submit" class="sidebar-logout-btn">Logout</button>
        </form>
    </div>
</aside>
@endsection

@section('topbar')
<header class="app-topbar">
    <button type="button" class="sidebar-toggle" @click="sidebarOpen = !sidebarOpen" aria-label="Toggle menu">
        <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
    </button>
    <div class="app-topbar-brand">
        <span class="app-topbar-school">Isabela National High School</span>
        <span class="app-topbar-system">SMART-DTR Employee Portal</span>
    </div>
    <div class="app-topbar-actions">
        <x-notification-bell guard="staff" variant="light" />
        <a href="{{ route('staff.profile') }}" class="app-topbar-user no-underline">
            <x-user-avatar :user="auth('staff')->user()" size="sm" />
            <div class="app-topbar-user-info">
                <span class="app-topbar-user-name">{{ auth('staff')->user()->name }}</span>
                <span class="app-topbar-user-role">{{ auth('staff')->user()->roleLabel() }}</span>
            </div>
        </a>
    </div>
</header>
@endsection
