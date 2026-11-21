<?php

namespace App\Concerns;

trait HasAvatar
{
    public function hasAvatar(): bool
    {
        return filled($this->avatar_path) && file_exists(public_path($this->avatar_path));
    }

    public function avatarUrl(): ?string
    {
        return $this->hasAvatar() ? asset($this->avatar_path) : null;
    }

    public function displayName(): string
    {
        return $this->name ?? $this->username ?? 'User';
    }

    public function initials(): string
    {
        $parts = preg_split('/\s+/', trim($this->displayName())) ?: [];
        $initials = '';

        foreach (array_slice($parts, 0, 2) as $part) {
            $initials .= mb_strtoupper(mb_substr($part, 0, 1));
        }

        return $initials !== '' ? $initials : '?';
    }
}
