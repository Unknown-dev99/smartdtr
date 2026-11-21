@props([
    'label' => 'Password',
    'confirmLabel' => 'Confirm',
    'min' => \App\Rules\StrongPassword::MIN_LENGTH,
])

<div
    class="password-fields"
    x-data="{
        password: '',
        confirm: '',
        min: {{ (int) $min }},
        get lengthOk() { return this.password.length >= this.min },
        get lowerOk() { return /[a-z]/.test(this.password) },
        get upperOk() { return /[A-Z]/.test(this.password) },
        get numberOk() { return /[0-9]/.test(this.password) },
        get symbolOk() { return /[^A-Za-z0-9]/.test(this.password) },
        get matchOk() { return this.confirm.length > 0 && this.password === this.confirm },
        get strong() { return this.lengthOk && this.lowerOk && this.upperOk && this.numberOk && this.symbolOk }
    }"
>
    <div class="grid grid-cols-2 gap-4">
        <div class="form-group">
            <label class="form-label">{{ $label }}</label>
            <input
                type="password"
                name="password"
                required
                minlength="{{ $min }}"
                autocomplete="new-password"
                class="form-input @error('password') form-input-error @enderror"
                x-model="password"
            >
            @error('password')
                <p class="form-error">{{ $message }}</p>
            @enderror
        </div>
        <div class="form-group">
            <label class="form-label">{{ $confirmLabel }}</label>
            <input
                type="password"
                name="password_confirmation"
                required
                minlength="{{ $min }}"
                autocomplete="new-password"
                class="form-input @error('password') form-input-error @enderror"
                x-model="confirm"
            >
        </div>
    </div>

    <ul class="password-rules" aria-live="polite">
        <li :class="lengthOk ? 'is-met' : ''">At least {{ $min }} characters</li>
        <li :class="upperOk ? 'is-met' : ''">One uppercase letter (A–Z)</li>
        <li :class="lowerOk ? 'is-met' : ''">One lowercase letter (a–z)</li>
        <li :class="numberOk ? 'is-met' : ''">One number (0–9)</li>
        <li :class="symbolOk ? 'is-met' : ''">One symbol (! @ # $ % …)</li>
        <li :class="matchOk ? 'is-met' : ''">Passwords match</li>
    </ul>
</div>
