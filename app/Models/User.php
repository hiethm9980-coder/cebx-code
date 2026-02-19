<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

/**
 * User model: intentionally does NOT use BelongsToAccount scope so that
 * the auth guard can retrieve the user by ID from session regardless of account.
 * Use User::where('account_id', $id) when querying users per account.
 */
class User extends Authenticatable
{
    use HasApiTokens, HasFactory, HasUuids, Notifiable, SoftDeletes;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'account_id',
        'name',
        'email',
        'password',
        'phone',
        'status',
        'is_owner',
        'locale',
        'timezone',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password'          => 'hashed',
        'is_owner'          => 'boolean',
    ];

    // ─── Relationships ────────────────────────────────────────────

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function roles(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'user_role')
                    ->withPivot(['assigned_by', 'assigned_at']);
    }

    // ─── Helpers ──────────────────────────────────────────────────

    public function isOwner(): bool
    {
        return $this->is_owner;
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Check if user has a specific permission (via any of their roles).
     * Account owners implicitly have ALL permissions.
     */
    public function hasPermission(string $permissionKey): bool
    {
        if ($this->is_owner) {
            return true;
        }

        return $this->roles()
            ->whereHas('permissions', fn ($q) => $q->where('key', $permissionKey))
            ->exists();
    }

    /**
     * Check if user has ANY of the given permissions.
     */
    public function hasAnyPermission(array $permissionKeys): bool
    {
        if ($this->is_owner) {
            return true;
        }

        return $this->roles()
            ->whereHas('permissions', fn ($q) => $q->whereIn('key', $permissionKeys))
            ->exists();
    }

    /**
     * Get all permission keys for this user (flattened from all roles).
     */
    public function allPermissions(): array
    {
        if ($this->is_owner) {
            return \App\Rbac\PermissionsCatalog::keys();
        }

        return $this->roles()
            ->with('permissions')
            ->get()
            ->pluck('permissions')
            ->flatten()
            ->pluck('key')
            ->unique()
            ->values()
            ->toArray();
    }
}
