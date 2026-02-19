<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Traits\BelongsToAccount;
use Illuminate\Support\Str;

/**
 * Store — Sales channel or sub-entity within an account.
 *
 * FR-IAM-009: Multi-store support
 * Foundation for FR-ST (Sales Channel Integration) module.
 */
class Store extends Model
{
    use HasUuids, HasFactory, SoftDeletes, BelongsToAccount;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'account_id',
        'name',
        'slug',
        'status',
        'platform',
        'contact_name',
        'contact_phone',
        'contact_email',
        'address_line_1',
        'address_line_2',
        'city',
        'state_province',
        'postal_code',
        'country',
        'currency',
        'language',
        'timezone',
        'logo_path',
        'url', // alias for website_url (mutator below)
        'website_url',
        'external_store_id',
        'external_store_url',
        'connection_config',
        'connection_status',
        'last_synced_at',
        'is_default',
        'created_by',
    ];

    protected $casts = [
        'connection_config' => 'encrypted:array',
        'is_default'        => 'boolean',
        'last_synced_at'    => 'datetime',
    ];

    protected $hidden = [
        'connection_config', // Never expose credentials in API
    ];

    // ─── Status Constants ────────────────────────────────────────

    public const STATUS_ACTIVE    = 'active';
    public const STATUS_INACTIVE  = 'inactive';
    public const STATUS_SUSPENDED = 'suspended';

    // ─── Platform Constants ──────────────────────────────────────

    public const PLATFORM_MANUAL      = 'manual';
    public const PLATFORM_SHOPIFY     = 'shopify';
    public const PLATFORM_WOOCOMMERCE = 'woocommerce';
    public const PLATFORM_SALLA       = 'salla';
    public const PLATFORM_ZID         = 'zid';
    public const PLATFORM_CUSTOM_API  = 'custom_api';

    public const ALL_PLATFORMS = [
        self::PLATFORM_MANUAL,
        self::PLATFORM_SHOPIFY,
        self::PLATFORM_WOOCOMMERCE,
        self::PLATFORM_SALLA,
        self::PLATFORM_ZID,
        self::PLATFORM_CUSTOM_API,
    ];

    // ─── Connection Status ───────────────────────────────────────

    public const CONNECTION_DISCONNECTED = 'disconnected';
    public const CONNECTION_CONNECTED    = 'connected';
    public const CONNECTION_ERROR        = 'error';

    // ─── Limits ──────────────────────────────────────────────────

    public const MAX_STORES_PER_ACCOUNT = 20;

    protected static function booted(): void
    {
        static::creating(function (Store $store): void {
            if (empty($store->slug) && ! empty($store->name) && ! empty($store->account_id)) {
                $store->slug = static::generateSlug($store->name, $store->account_id);
            }
        });
    }

    /** Allow mass assignment of url → persists as website_url. */
    public function setUrlAttribute(?string $value): void
    {
        $this->attributes['website_url'] = $value;
        unset($this->attributes['url']);
    }

    /** Normalize platform to lowercase to match DB enum (e.g. "Shopify" → "shopify"). */
    public function setPlatformAttribute(?string $value): void
    {
        $this->attributes['platform'] = $value !== null ? strtolower($value) : null;
    }

    // ─── Relationships ───────────────────────────────────────────

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function orders(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(\App\Models\Order::class);
    }

    // ─── Helpers ─────────────────────────────────────────────────

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function isConnected(): bool
    {
        return $this->connection_status === self::CONNECTION_CONNECTED;
    }

    public function isManual(): bool
    {
        return $this->platform === self::PLATFORM_MANUAL;
    }

    public function isDefault(): bool
    {
        return $this->is_default;
    }

    /**
     * Get platform display info.
     */
    public function platformDisplay(): array
    {
        return match ($this->platform) {
            self::PLATFORM_MANUAL      => ['name' => 'متجر يدوي', 'name_en' => 'Manual Store', 'icon' => 'store'],
            self::PLATFORM_SHOPIFY     => ['name' => 'شوبيفاي', 'name_en' => 'Shopify', 'icon' => 'shopping-bag'],
            self::PLATFORM_WOOCOMMERCE => ['name' => 'ووكومرس', 'name_en' => 'WooCommerce', 'icon' => 'shopping-cart'],
            self::PLATFORM_SALLA       => ['name' => 'سلة', 'name_en' => 'Salla', 'icon' => 'package'],
            self::PLATFORM_ZID         => ['name' => 'زد', 'name_en' => 'Zid', 'icon' => 'box'],
            self::PLATFORM_CUSTOM_API  => ['name' => 'API مخصص', 'name_en' => 'Custom API', 'icon' => 'code'],
            default                    => ['name' => $this->platform, 'name_en' => $this->platform, 'icon' => 'store'],
        };
    }

    /**
     * Generate unique slug within account.
     */
    public static function generateSlug(string $name, string $accountId): string
    {
        $slug = Str::slug($name);
        $original = $slug;
        $counter = 1;

        while (static::withoutGlobalScopes()
            ->where('account_id', $accountId)
            ->where('slug', $slug)
            ->exists()
        ) {
            $slug = $original . '-' . $counter++;
        }

        return $slug;
    }

    // ─── Scopes ──────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeForPlatform($query, string $platform)
    {
        return $query->where('platform', $platform);
    }

    public function scopeConnected($query)
    {
        return $query->where('connection_status', self::CONNECTION_CONNECTED);
    }
}
