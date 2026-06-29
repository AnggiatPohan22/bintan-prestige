<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'ui_mode'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public const ROLE_SUPER_ADMIN = 'super_admin';

    public const ROLE_ADMIN = 'admin';

    public const ADMIN_ROLES = [
        self::ROLE_SUPER_ADMIN,
        self::ROLE_ADMIN,
    ];

    public const UI_MODE_AUTO  = 'auto';

    public const UI_MODE_DARK  = 'dark';

    public const UI_MODE_LIGHT = 'light';

    public const UI_MODES = [
        self::UI_MODE_AUTO,
        self::UI_MODE_DARK,
        self::UI_MODE_LIGHT,
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'is_admin' => 'boolean',
            'is_active' => 'boolean',
            'password' => 'hashed',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->canAccessAdmin();
    }

    public function canAccessAdmin(): bool
    {
        return (bool) $this->is_admin
            && (bool) ($this->is_active ?? true)
            && in_array($this->role, self::ADMIN_ROLES, true);
    }

    public function isSuperAdmin(): bool
    {
        return $this->canAccessAdmin()
            && $this->role === self::ROLE_SUPER_ADMIN;
    }
}
