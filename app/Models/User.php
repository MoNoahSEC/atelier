<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'password_changed_at',
        'password_reset_requested_at',
        'password_reset_requested_by',
        'last_login_at',
        'is_admin',
        'admin_role',
        'is_banned',
        'banned_at',
        'ban_reason',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at'           => 'datetime',
            'password_changed_at'         => 'datetime',
            'password_reset_requested_at' => 'datetime',
            'last_login_at'               => 'datetime',
            'banned_at'                   => 'datetime',
            'is_admin'                    => 'boolean',
            'is_banned'                   => 'boolean',
        ];
    }

    public function addresses(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Address::class);
    }

    public function orders(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Order::class, 'customer_email', 'email');
    }

    public function defaultAddress(): ?Address
    {
        return $this->addresses()->where('is_default', true)->first()
            ?? $this->addresses()->latest()->first();
    }

    public function isAdmin(): bool
    {
        return (bool) $this->is_admin;
    }

    public function isSuperAdmin(): bool
    {
        return $this->isAdmin() && ($this->admin_role === 'super_admin' || $this->admin_role === null);
    }

    public function isManager(): bool
    {
        return $this->isAdmin() && in_array($this->admin_role, ['super_admin', 'manager', null], true);
    }

    public function isStaff(): bool
    {
        return $this->isAdmin();
    }

    public function hasRole(string|array $roles): bool
    {
        if (!$this->isAdmin()) {
            return false;
        }

        $roles = (array) $roles;
        $currentRole = $this->admin_role ?: 'super_admin';

        // Super admins always have access to everything
        if ($currentRole === 'super_admin') {
            return true;
        }

        return in_array($currentRole, $roles, true);
    }

    public function isBanned(): bool
    {
        return (bool) $this->is_banned;
    }
}
