<?php

namespace App\Models;

use App\Services\Auth\PermissionResolver;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens;
    use HasFactory;
    use HasUuids;
    use SoftDeletes;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $guarded = [];
    protected $hidden = ['password', 'remember_token'];
    protected $casts = [
        'last_login_at' => 'datetime',
        'is_active' => 'boolean',
        'user_type' => 'string',
    ];

    public function account(): BelongsTo { return $this->belongsTo(Account::class); }
    public function branch(): BelongsTo { return $this->belongsTo(Branch::class); }
    public function shipments(): HasMany { return $this->hasMany(Shipment::class); }
    public function tickets(): HasMany { return $this->hasMany(SupportTicket::class); }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'user_role')
            ->withPivot(['assigned_by', 'assigned_at']);
    }

    public function hasPermission(string $permission): bool
    {
        // Normalise colon-notation to dot-notation (e.g. shipments:view → shipments.view)
        // so that service-layer colon keys work the same as resolver dot keys.
        $permission = str_replace(':', '.', trim($permission));

        if ($permission === '') {
            return false;
        }

        // Account owners (external users) implicitly hold all permissions.
        // Internal users always go through explicit RBAC only.
        if ((bool) ($this->is_owner ?? false) && strtolower((string) ($this->user_type ?? 'external')) !== 'internal') {
            return true;
        }

        try {
            return app(PermissionResolver::class)->can($this, $permission);
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Compatibility shim: tests may pass role_id from the old single-role pattern.
     * The 2026 schema uses the user_role pivot; ignore role_id on the users table.
     */
    public function setRoleIdAttribute(mixed $value): void
    {
        // Intentionally a no-op — role assignment is handled via the user_role pivot.
        // Silently discarding role_id prevents "Unknown column" errors when factories
        // or seeders pass role_id for legacy compatibility.
    }

    /**
     * @return array<int, string>
     */
    public function allPermissions(): array
    {
        // Account owners (external users) implicitly hold all external permissions.
        if ((bool) ($this->is_owner ?? false) && strtolower((string) ($this->user_type ?? 'external')) !== 'internal') {
            try {
                $query = \Illuminate\Support\Facades\DB::table('permissions');
                if (\Illuminate\Support\Facades\Schema::hasColumn('permissions', 'audience')) {
                    $query->whereIn('audience', ['external', 'both']);
                }
                return $query->distinct()->pluck('key')->filter(static fn ($k) => $k !== null && !str_contains((string) $k, ':'))->values()->all();
            } catch (\Throwable $e) {
                return [];
            }
        }

        try {
            $keys = app(PermissionResolver::class)->all($this);
            // Convert dot-notation back to colon-notation (group:rest) so that callers
            // using either notation can find their permission in the returned list.
            // syncRolePermissions() already normalises colon→dot before comparing,
            // so this format is safe for all internal usages.
            return array_map(
                static fn (string $k): string => (string) preg_replace('/\./', ':', $k, 1),
                $keys
            );
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * @return array<int, string>
     */
    public function getAllPermissions(): array
    {
        return $this->allPermissions();
    }

    /**
     * Grant a permission to this user by permission key/name.
     * Creates or finds the permission, creates or finds a role for it,
     * and attaches both the role and permission.
     *
     * The PermissionResolver only resolves dot-notation keys (no colons).
     * Colon-notation is automatically converted to dot-notation so that
     * hasPermission() can resolve the granted permission.
     */
    public function grantPermission(string $permissionName): void
    {
        // Normalise colon notation (e.g. shipments:manage) to dot notation (shipments.manage)
        // so the PermissionResolver can resolve it.
        $permissionName = str_replace(':', '.', $permissionName);

        $accountId = (string) ($this->account_id ?? '');

        $permission = Permission::query()->firstOrCreate(
            ['key' => $permissionName],
            [
                'group'        => explode('.', str_replace(':', '.', $permissionName))[0],
                'display_name' => $permissionName,
                'description'  => $permissionName,
                'audience'     => Schema::hasColumn('permissions', 'audience') ? 'external' : null,
            ]
        );

        // Find or create a role scoped to this user's account for this permission
        $roleSlug  = 'auto_' . Str::slug($permissionName, '_');
        $roleName  = 'auto_' . $permissionName;

        $roleAttributes = [
            'name'         => $roleName,
            'display_name' => $roleName,
            'description'  => 'Auto-generated role for test permission grant',
            'is_system'    => false,
            'template'     => null,
        ];

        if ($accountId !== '') {
            $role = Role::withoutGlobalScopes()->firstOrCreate(
                ['account_id' => $accountId, 'slug' => $roleSlug],
                $roleAttributes
            );
        } else {
            $role = Role::withoutGlobalScopes()->firstOrCreate(
                ['slug' => $roleSlug],
                $roleAttributes
            );
        }

        $role->permissions()->syncWithoutDetaching([
            (string) $permission->id => ['granted_at' => now()],
        ]);

        $this->roles()->syncWithoutDetaching([
            (string) $role->id => [
                'assigned_by' => null,
                'assigned_at' => now(),
            ],
        ]);
    }
}
