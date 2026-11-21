@extends('layouts.app')

@section('body_class', '')
@section('main_class', '')
@section('hide_footer', true)
@section('title', 'Reset Password - SMARTDTR')

@section('content')
<div class="auth-shell">
    <div class="auth-card">
        <div class="auth-logo-wrap">
            <div class="auth-logo">New Password</div>
            <p class="auth-subtitle">Enter your new password below</p>
        </div>

        <form method="POST" action="{{ route('password.update', $guard) }}" class="space-y-4">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">
            <input type="hidden" name="email" value="{{ $email }}">
            <x-password-fields label="New Password" confirm-label="Confirm Password" />
            <button type="submit" class="btn-primary w-full py-3">Update Password</button>
        </form>
    </div>
</div>
@endsection
