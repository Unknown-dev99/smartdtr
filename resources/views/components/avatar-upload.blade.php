@props(['user', 'guard'])

<div class="avatar-upload-card">
    <div class="avatar-upload-preview">
        <x-user-avatar :user="$user" size="2xl" />
    </div>
    <div class="avatar-upload-actions">
        <p class="text-sm font-semibold text-slate-800">Profile Photo <span class="text-slate-400 font-normal">(optional)</span></p>
        <p class="text-xs text-slate-500 mt-1 mb-4">JPG, PNG, or WebP · Max 2 MB</p>

        <form method="POST" action="{{ $guard === 'staff' ? route('staff.avatar.upload') : route('admin.avatar.upload') }}"
            enctype="multipart/form-data" class="space-y-3"
            data-confirm="Upload this photo as your profile avatar?"
            data-confirm-button="Upload">
            @csrf
            <input type="file" name="avatar" accept="image/jpeg,image/png,image/webp" required class="form-input text-sm">
            <button type="submit" class="btn-primary btn-sm">Upload Photo</button>
        </form>

        @if($user->hasAvatar())
        <form method="POST" action="{{ $guard === 'staff' ? route('staff.avatar.remove') : route('admin.avatar.remove') }}" class="mt-3"
            data-confirm="Remove your profile photo?"
            data-confirm-type="danger"
            data-confirm-button="Remove">
            @csrf @method('DELETE')
            <button type="submit" class="btn-danger btn-sm">Remove Photo</button>
        </form>
        @endif
    </div>
</div>
