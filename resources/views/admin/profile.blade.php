@extends('layouts.admin')

@section('title', 'My Profile - SMARTDTR')

@section('content')
<x-page-header title="My Profile" subtitle="Your admin account" />

<x-avatar-upload :user="$admin" guard="admin" />

<div class="app-card max-w-2xl">
    <div class="app-card-header">
        <h2 class="app-card-title">Account Details</h2>
    </div>
    <div class="app-card-body space-y-4">
        <div class="grid grid-cols-2 gap-4 text-sm">
            <div><span class="text-slate-500">Username</span><p class="font-medium">{{ $admin->username }}</p></div>
            <div><span class="text-slate-500">Email</span><p class="font-medium">{{ $admin->email ?? '—' }}</p></div>
            <div><span class="text-slate-500">Role</span><p class="font-medium">{{ $admin->roleLabel() }}</p></div>
        </div>
    </div>
</div>
@endsection
