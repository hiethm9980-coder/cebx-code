<?php
namespace App\Models;
use App\Models\Traits\BelongsToAccount;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, HasMany};
use Illuminate\Database\Eloquent\SoftDeletes;
class Store extends Model {
    use HasFactory, HasUuids, BelongsToAccount, SoftDeletes;

    // ─── Status ──────────────────────────────────────────────────
    public const STATUS_ACTIVE    = 'active';
    public const STATUS_INACTIVE  = 'inactive';
    public const STATUS_SUSPENDED = 'suspended';

    // ─── Platforms ───────────────────────────────────────────────
    public const PLATFORM_MANUAL     = 'manual';
    public const PLATFORM_SHOPIFY    = 'shopify';
    public const PLATFORM_WOOCOMMERCE = 'woocommerce';
    public const PLATFORM_SALLA      = 'salla';
    public const PLATFORM_ZID        = 'zid';
    public const PLATFORM_CUSTOM_API = 'custom_api';

    public const ALL_PLATFORMS = [
        self::PLATFORM_MANUAL,
        self::PLATFORM_SHOPIFY,
        self::PLATFORM_WOOCOMMERCE,
        self::PLATFORM_SALLA,
        self::PLATFORM_ZID,
        self::PLATFORM_CUSTOM_API,
    ];

    // ─── Connection Status ────────────────────────────────────────
    public const CONNECTION_DISCONNECTED = 'disconnected';
    public const CONNECTION_CONNECTED    = 'connected';
    public const CONNECTION_ERROR        = 'error';

    // ─── Limits ───────────────────────────────────────────────────
    public const MAX_STORES_PER_ACCOUNT = 5;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $guarded = [];
    protected $casts = ['last_sync_at' => 'datetime', 'is_default' => 'boolean', 'connection_config' => 'array'];
    public function account(): BelongsTo { return $this->belongsTo(Account::class); }
    public function orders(): HasMany { return $this->hasMany(Order::class); }

    // ─── Helpers ─────────────────────────────────────────────────

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function platformDisplay(): string
    {
        return match ($this->platform) {
            self::PLATFORM_SHOPIFY     => 'Shopify',
            self::PLATFORM_WOOCOMMERCE => 'WooCommerce',
            self::PLATFORM_SALLA       => 'Salla',
            self::PLATFORM_ZID         => 'Zid',
            self::PLATFORM_CUSTOM_API  => 'Custom API',
            default                    => 'Manual',
        };
    }

    /**
     * Generate a URL-safe unique slug for a store name within an account.
     */
    public static function generateSlug(string $name, string $accountId): string
    {
        $base = \Illuminate\Support\Str::slug($name);
        if (!static::withoutGlobalScopes()->where('account_id', $accountId)->where('slug', $base)->exists()) {
            return $base;
        }
        $i = 1;
        while (static::withoutGlobalScopes()->where('account_id', $accountId)->where('slug', "{$base}-{$i}")->exists()) {
            $i++;
        }
        return "{$base}-{$i}";
    }
}
