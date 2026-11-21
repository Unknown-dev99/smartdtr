@extends($guard === 'staff' ? 'layouts.staff' : 'layouts.admin')

@section('title', 'Change Password - SMARTDTR')

@section('content')
<x-page-header title="Change Password" subtitle="Update your account password" />

<div class="app-card max-w-md">
    <div class="app-card-body">
        <form method="POST" action="{{ $guard === 'staff' ? route('staff.password.change') : route('admin.password.change') }}" class="space-y-4"
            data-confirm="Change your password?"
            data-confirm-button="Update Password">
            @csrf
            <div class="form-group">
                <label class="form-label">Current Password</label>
                <input type="password" name="current_password" required class="form-input">
            </div>
            <x-password-fields label="New Password" confirm-label="Confirm New Password" />
            <button type="submit" class="btn-primary">Update Password</button>
        </form>
    </div>
</div>
@endsection
