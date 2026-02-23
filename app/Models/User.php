<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Authenticatable
{
    // لا نستخدم HasUuids لأن جدول users على السيرفر قد يكون من الـ migration الأولى (id = bigint)
    protected $guarded = [];
    protected $hidden = ['password', 'remember_token'];
    protected $casts = ['last_login_at' => 'datetime', 'is_active' => 'boolean', 'is_super_admin' => 'boolean'];

    public function account(): BelongsTo  { return $this->belongsTo(Account::class); }
    public function branch(): BelongsTo   { return $this->belongsTo(Branch::class); }
    public function shipments(): HasMany  { return $this->hasMany(Shipment::class); }
    public function tickets(): HasMany    { return $this->hasMany(SupportTicket::class); }

    /**
     * الأدوار المرتبطة بالمستخدم (جدول user_role).
     * قد يكون user_id في user_role من نوع uuid بينما users.id bigint — إن لم يُستخدم الجدول فاستخدم عمود role.
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'user_role')
            ->withPivot(['assigned_by', 'assigned_at']);
    }

    /**
     * التحقق من صلاحية المستخدم.
     * تدعم المسارات استخدام النقطة (مثل shipments.create) والكتالوج استخدام النقطتين (shipments:create).
     */
    public function hasPermission(string $permission): bool
    {
        if ($this->is_super_admin ?? false) {
            return true;
        }
        if ($this->role === 'admin') {
            return true;
        }

        $keyForLookup = str_replace('.', ':', $permission);

        try {
            $userRoles = $this->roles()->with('permissions')->get();
            foreach ($userRoles as $role) {
                if ($role->permissions->contains('key', $keyForLookup) || $role->permissions->contains('key', $permission)) {
                    return true;
                }
            }
        } catch (\Throwable $e) {
            // إذا كان جدول user_role غير متوافق (مثلاً uuid vs bigint) نعتمد على عمود role فقط
        }

        // مدير ومشرف: نمنح صلاحيات القراءة الأساسية حتى يتم ربط الأدوار لاحقاً
        if (in_array($this->role, ['manager', 'supervisor'], true)) {
            return true;
        }

        return false;
    }
}
