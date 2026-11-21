@extends('layouts.app')

@section('body_class', '')
@section('main_class', '')
@section('hide_footer')
@section('title', 'Admin Login - SMARTDTR')

@section('content')
<div class="auth-shell">
    <div class="auth-card">
        <div class="auth-logo-wrap">
            <div class="auth-logo-badge"><x-logo size="xl" class="auth-logo-img" /></div>
            <div class="auth-logo">SMART-DTR</div>
            <p class="auth-subtitle">HR / Admin Sign In</p>
        </div>

        <form method="POST" action="{{ route('login') }}" class="space-y-5">
            @csrf
            <div class="form-group">
                <label class="form-label">Username or email</label>
                <input type="text" name="username" value="{{ old('username') }}" required autofocus autocomplete="username" class="form-input" placeholder="Enter username or email">
            </div>
            <div class="form-group">
                <label class="form-label">Password</label>
                <input type="password" name="password" required autocomplete="current-password" class="form-input" placeholder="Enter password">
            </div>
            <button type="submit" class="btn-primary w-full py-3">Sign In to Admin</button>
        </form>

        <div class="auth-divider">or</div>

        <div class="text-center text-sm text-slate-500 space-y-2">
            <p><a href="{{ route('home') }}" class="auth-link">← Back to home</a></p>
            <p>Staff member? <a href="{{ route('staff.login') }}" class="auth-link">Staff portal login</a></p>
        </div>
    </div>
</div>
@endsection
