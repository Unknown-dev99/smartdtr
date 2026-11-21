<?php

namespace App\Models;

use App\Concerns\HasAvatar;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class AdminUser extends Authenticatable
{
    use HasAvatar, Notifiable;

    protected $table = 'admin_users';

    protected $fillable = ['username', 'email', 'password', 'role', 'avatar_path', 'reset_token', 'reset_token_expires_at'];

    protected $hidden = ['password', 'reset_token'];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'reset_token_expires_at' => 'datetime',
        ];
    }

    public function roleLabel(): string
    {
        return match ($this->role) {
            'super_admin' => 'Super Admin',
            'hr_officer' => 'HR Officer',
            'principal' => 'Principal',
            default => ucfirst(str_replace('_', ' ', $this->role)),
        };
    }

    public function hasPermission(string $permission): bool
    {
        $permissions = config('smartdtr.admin_roles.'.$this->role, []);

        return in_array('*', $permissions, true) || in_array($permission, $permissions, true);
    }

    public function canWrite(): bool
    {
        return $this->hasPermission('*') || $this->role !== 'principal';
    }
}
