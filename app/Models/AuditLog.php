<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Traits\BelongsToAccount;
use App\Exceptions\BusinessException;

/**
 * AuditLog — Immutable, append-only audit record.
 *
 * FR-IAM-006: Comprehensive audit logging
 * FR-IAM-013: Organization/team audit context
 *
 * CRITICAL: This model enforces append-only behavior.
 * Updates and deletes are blocked at the model level.
 */
class AuditLog extends Model
{
    use HasUuids, BelongsToAccount;

    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        'account_id',
        'user_id',
        'action',
        'severity',
        'category',
        'entity_type',
        'entity_id',
        'old_values',
        'new_values',
        'metadata',
        'ip_address',
        'user_agent',
        'request_id',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'metadata'   => 'array',
        'created_at' => 'datetime',
    ];

    // ─── Severity Constants ──────────────────────────────────────

    const SEVERITY_INFO     = 'info';
    const SEVERITY_WARNING  = 'warning';
    const SEVERITY_CRITICAL = 'critical';

    // ─── Category Constants ──────────────────────────────────────

    const CATEGORY_AUTH       = 'auth';
    const CATEGORY_USERS      = 'users';
    const CATEGORY_ROLES      = 'roles';
    const CATEGORY_ACCOUNT    = 'account';
    const CATEGORY_INVITATION = 'invitation';
    const CATEGORY_KYC        = 'kyc';
    const CATEGORY_FINANCIAL  = 'financial';
    const CATEGORY_SETTINGS   = 'settings';
    const CATEGORY_EXPORT     = 'export';
    const CATEGORY_SYSTEM     = 'system';

    // ─── Relationships ───────────────────────────────────────────

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function performer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // ─── Append-Only Enforcement ─────────────────────────────────

    /**
     * BLOCK all updates — audit logs are immutable.
     */
    public function update(array $attributes = [], array $options = [])
    {
        throw new BusinessException(
            'سجلات التدقيق غير قابلة للتعديل.',
            'ERR_AUDIT_IMMUTABLE',
            403
        );
    }

    /**
     * BLOCK all deletes — audit logs are immutable.
     */
    public function delete()
    {
        throw new BusinessException(
            'سجلات التدقيق غير قابلة للحذف.',
            'ERR_AUDIT_IMMUTABLE',
            403
        );
    }

    /**
     * BLOCK force delete.
     */
    public function forceDelete()
    {
        throw new BusinessException(
            'سجلات التدقيق غير قابلة للحذف.',
            'ERR_AUDIT_IMMUTABLE',
            403
        );
    }

    // ─── Scopes ──────────────────────────────────────────────────

    public function scopeForAccount($query, string $accountId)
    {
        return $query->where('account_id', $accountId);
    }

    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    public function scopeBySeverity($query, string $severity)
    {
        return $query->where('severity', $severity);
    }

    public function scopeByActor($query, string $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByAction($query, string $action)
    {
        return $query->where('action', $action);
    }

    public function scopeByEntity($query, string $entityType, ?string $entityId = null)
    {
        $query->where('entity_type', $entityType);
        if ($entityId) {
            $query->where('entity_id', $entityId);
        }
        return $query;
    }

    public function scopeDateRange($query, ?string $from = null, ?string $to = null)
    {
        if ($from) {
            $query->where('created_at', '>=', $from);
        }
        if ($to) {
            $query->where('created_at', '<=', $to);
        }
        return $query;
    }

    public function scopeByRequestId($query, string $requestId)
    {
        return $query->where('request_id', $requestId);
    }

    // ─── Helpers ─────────────────────────────────────────────────

    public static function categories(): array
    {
        return [
            self::CATEGORY_AUTH, self::CATEGORY_USERS, self::CATEGORY_ROLES,
            self::CATEGORY_ACCOUNT, self::CATEGORY_INVITATION, self::CATEGORY_KYC,
            self::CATEGORY_FINANCIAL, self::CATEGORY_SETTINGS, self::CATEGORY_EXPORT,
            self::CATEGORY_SYSTEM,
        ];
    }

    public static function severities(): array
    {
        return [self::SEVERITY_INFO, self::SEVERITY_WARNING, self::SEVERITY_CRITICAL];
    }
}
