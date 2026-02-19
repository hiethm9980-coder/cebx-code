<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Traits\BelongsToAccount;

/**
 * Canonical Order — FR-ST-004
 *
 * Unified order model regardless of source (Shopify, WooCommerce, Manual, etc.)
 * Unique per account+store+external_order_id (FR-ST-005 dedup).
 */
class Order extends Model
{
    use HasUuids, HasFactory, SoftDeletes, BelongsToAccount;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'account_id', 'store_id', 'external_order_id', 'external_order_number', 'source',
        'status', 'customer_name', 'customer_email', 'customer_phone',
        'shipping_name', 'shipping_phone',
        'shipping_address', // accepted for mass assign; mutator maps to shipping_* fields
        'shipping_address_line_1', 'shipping_address_line_2',
        'shipping_city', 'shipping_state', 'shipping_postal_code', 'shipping_country',
        'subtotal', 'shipping_cost', 'tax_amount', 'discount_amount', 'total_amount', 'currency',
        'total_weight', 'items_count', 'shipment_id',
        'auto_ship_eligible', 'hold_reason', 'rule_evaluation_log',
        'raw_payload', 'metadata',
        'external_created_at', 'external_updated_at', 'imported_at', 'imported_by',
        'order_number', // alias for external_order_number (mutator below)
    ];

    protected $casts = [
        'subtotal'             => 'decimal:2',
        'shipping_cost'        => 'decimal:2',
        'tax_amount'           => 'decimal:2',
        'discount_amount'      => 'decimal:2',
        'total_amount'         => 'decimal:2',
        'total_weight'         => 'decimal:3',
        'items_count'          => 'integer',
        'auto_ship_eligible'   => 'boolean',
        'rule_evaluation_log'  => 'array',
        'raw_payload'          => 'array',
        'metadata'             => 'array',
        'external_created_at'  => 'datetime',
        'external_updated_at'  => 'datetime',
        'imported_at'          => 'datetime',
    ];

    protected $hidden = ['raw_payload'];

    // ─── Status Constants ────────────────────────────────────────

    public const STATUS_PENDING    = 'pending';
    public const STATUS_READY      = 'ready';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_SHIPPED    = 'shipped';
    public const STATUS_DELIVERED  = 'delivered';
    public const STATUS_CANCELLED  = 'cancelled';
    public const STATUS_ON_HOLD    = 'on_hold';
    public const STATUS_FAILED     = 'failed';

    public const STATUSES = [
        self::STATUS_PENDING, self::STATUS_READY, self::STATUS_PROCESSING,
        self::STATUS_SHIPPED, self::STATUS_DELIVERED, self::STATUS_CANCELLED,
        self::STATUS_ON_HOLD, self::STATUS_FAILED,
    ];

    // Statuses that allow shipment creation
    public const SHIPPABLE_STATUSES = [self::STATUS_PENDING, self::STATUS_READY];

    // ─── Source Constants ────────────────────────────────────────

    public const SOURCE_MANUAL      = 'manual';
    public const SOURCE_SHOPIFY     = 'shopify';
    public const SOURCE_WOOCOMMERCE = 'woocommerce';
    public const SOURCE_SALLA       = 'salla';
    public const SOURCE_ZID         = 'zid';
    public const SOURCE_CUSTOM_API  = 'custom_api';

    // ─── Relationships ───────────────────────────────────────────

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function importedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'imported_by');
    }

    // ─── Status Helpers ──────────────────────────────────────────

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isShippable(): bool
    {
        return in_array($this->status, self::SHIPPABLE_STATUSES);
    }

    public function isShipped(): bool
    {
        return $this->status === self::STATUS_SHIPPED;
    }

    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    public function isOnHold(): bool
    {
        return $this->status === self::STATUS_ON_HOLD;
    }

    public function hasShipment(): bool
    {
        return $this->shipment_id !== null;
    }

    public function isManual(): bool
    {
        return $this->source === self::SOURCE_MANUAL;
    }

    // ─── Status Display ──────────────────────────────────────────

    public function statusDisplay(): array
    {
        return match ($this->status) {
            self::STATUS_PENDING    => ['label' => 'قيد الانتظار', 'color' => 'yellow'],
            self::STATUS_READY      => ['label' => 'جاهز للشحن', 'color' => 'blue'],
            self::STATUS_PROCESSING => ['label' => 'قيد المعالجة', 'color' => 'orange'],
            self::STATUS_SHIPPED    => ['label' => 'تم الشحن', 'color' => 'green'],
            self::STATUS_DELIVERED  => ['label' => 'تم التسليم', 'color' => 'emerald'],
            self::STATUS_CANCELLED  => ['label' => 'ملغى', 'color' => 'red'],
            self::STATUS_ON_HOLD    => ['label' => 'معلق', 'color' => 'gray'],
            self::STATUS_FAILED     => ['label' => 'فشل', 'color' => 'red'],
            default                 => ['label' => $this->status, 'color' => 'gray'],
        };
    }

    public function sourceDisplay(): array
    {
        return match ($this->source) {
            self::SOURCE_MANUAL      => ['name' => 'يدوي', 'icon' => 'edit'],
            self::SOURCE_SHOPIFY     => ['name' => 'شوبيفاي', 'icon' => 'shopping-bag'],
            self::SOURCE_WOOCOMMERCE => ['name' => 'ووكومرس', 'icon' => 'shopping-cart'],
            self::SOURCE_SALLA       => ['name' => 'سلة', 'icon' => 'store'],
            self::SOURCE_ZID         => ['name' => 'زد', 'icon' => 'store'],
            self::SOURCE_CUSTOM_API  => ['name' => 'API مخصص', 'icon' => 'code'],
            default                  => ['name' => $this->source, 'icon' => 'box'],
        };
    }

    // ─── Scopes ──────────────────────────────────────────────────

    public function scopeShippable($query)
    {
        return $query->whereIn('status', self::SHIPPABLE_STATUSES);
    }

    public function scopeForStore($query, string $storeId)
    {
        return $query->where('store_id', $storeId);
    }

    public function scopeForSource($query, string $source)
    {
        return $query->where('source', $source);
    }

    public function scopeWithStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /** Human-readable order number (alias for external_order_number). */
    public function getOrderNumberAttribute(): ?string
    {
        return $this->external_order_number;
    }

    /** Allow mass assignment of order_number → persists as external_order_number. */
    public function setOrderNumberAttribute(?string $value): void
    {
        $this->attributes['external_order_number'] = $value;
        unset($this->attributes['order_number']);
    }

    /** Allow mass assignment of shipping_address (string or array) → maps to shipping_* fields. */
    public function setShippingAddressAttribute(mixed $value): void
    {
        if (is_array($value)) {
            $map = [
                'shipping_name' => ['name', 'shipping_name'],
                'shipping_phone' => ['phone', 'shipping_phone'],
                'shipping_address_line_1' => ['address_1', 'address_line_1', 'line_1', 'address'],
                'shipping_address_line_2' => ['address_2', 'address_line_2', 'line_2'],
                'shipping_city' => ['city'],
                'shipping_state' => ['state'],
                'shipping_postal_code' => ['postal_code', 'postal'],
                'shipping_country' => ['country'],
            ];
            foreach ($map as $col => $keys) {
                foreach ($keys as $key) {
                    if (array_key_exists($key, $value) && $value[$key] !== null && $value[$key] !== '') {
                        $this->attributes[$col] = $value[$key];
                        break;
                    }
                }
            }
        } elseif (is_string($value)) {
            $this->attributes['shipping_address_line_1'] = $value;
        }
        // Remove virtual key so it is not sent to DB
        unset($this->attributes['shipping_address']);
    }

    // ─── Shipping Address Helper ─────────────────────────────────

    public function shippingAddress(): array
    {
        return array_filter([
            'name'         => $this->shipping_name,
            'phone'        => $this->shipping_phone,
            'address_1'    => $this->shipping_address_line_1,
            'address_2'    => $this->shipping_address_line_2,
            'city'         => $this->shipping_city,
            'state'        => $this->shipping_state,
            'postal_code'  => $this->shipping_postal_code,
            'country'      => $this->shipping_country,
        ]);
    }
}
