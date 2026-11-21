@extends('layouts.app')

@section('body_class', '')
@section('main_class', '')
@section('hide_footer')
@section('title', 'Employee Login - SMARTDTR')

@section('content')
<div class="auth-shell">
    <div class="auth-card">
        <div class="auth-logo-wrap">
            <div class="auth-logo-badge"><x-logo size="xl" class="auth-logo-img" /></div>
            <div class="auth-logo">SMART-DTR</div>
            <p class="auth-subtitle">Employee Portal — Sign in using your registered email</p>
        </div>

        <form method="POST" action="{{ route('staff.login') }}" class="space-y-5">
            @csrf
            <div class="form-group">
                <label class="form-label">Email / Gmail Address</label>
                <input type="email" name="email" value="{{ old('email') }}" required autofocus
                    autocomplete="username" inputmode="email"
                    class="form-input @error('email') form-input-error @enderror" placeholder="you@gmail.com">
                @error('email')<p class="form-error">{{ $message }}</p>@enderror
            </div>
            <div class="form-group">
                <label class="form-label">Password</label>
                <input type="password" name="password" required class="form-input" placeholder="Your password">
            </div>
            <button type="submit" class="btn-primary w-full py-3">Sign In</button>
        </form>

        <div class="auth-divider">new here?</div>

        <div class="text-center text-sm text-slate-500 space-y-2">
            <p><a href="{{ route('staff.register') }}" class="auth-link">Create an account →</a></p>
            <p><a href="{{ route('home') }}" class="auth-link">← Back to home</a></p>
        </div>
    </div>
</div>
@endsection
