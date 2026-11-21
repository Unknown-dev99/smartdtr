<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class StrongPassword implements ValidationRule
{
    public const MIN_LENGTH = 8;

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $value = (string) $value;
        $missing = [];

        if (strlen($value) < self::MIN_LENGTH) {
            $missing[] = 'at least 8 characters';
        }
        if (! preg_match('/[a-z]/', $value)) {
            $missing[] = 'a lowercase letter';
        }
        if (! preg_match('/[A-Z]/', $value)) {
            $missing[] = 'an uppercase letter';
        }
        if (! preg_match('/[0-9]/', $value)) {
            $missing[] = 'a number';
        }
        if (! preg_match('/[^A-Za-z0-9]/', $value)) {
            $missing[] = 'a symbol (for example ! @ # $ %)';
        }

        if ($missing !== []) {
            $fail('Password must include '.implode(', ', $missing).'.');
        }
    }
}
