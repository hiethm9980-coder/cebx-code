<?php
namespace App\Models;
use App\Models\Traits\BelongsToAccount;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class Order extends Model {
    use HasFactory, HasUuids, BelongsToAccount;

    // ─── Sources ──────────────────────────────────────────────────
    public const SOURCE_MANUAL     = 'manual';
    public const SOURCE_SHOPIFY    = 'shopify';
    public const SOURCE_WOOCOMMERCE = 'woocommerce';
    public const SOURCE_SALLA      = 'salla';
    public const SOURCE_ZID        = 'zid';
    public const SOURCE_CUSTOM_API = 'custom_api';

    // ─── Statuses ─────────────────────────────────────────────────
    public const STATUS_PENDING    = 'pending';
    public const STATUS_READY      = 'ready';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_SHIPPED    = 'shipped';
    public const STATUS_DELIVERED  = 'delivered';
    public const STATUS_CANCELLED  = 'cancelled';
    public const STATUS_ON_HOLD    = 'on_hold';
    public const STATUS_FAILED     = 'failed';

    public $incrementing = false;
    protected $keyType = 'string';

    protected $guarded = [];
    protected $casts = [
        'auto_ship_eligible'  => 'boolean',
        'rule_evaluation_log' => 'array',
        'metadata'            => 'array',
        'imported_at'         => 'datetime',
    ];

    public function account(): BelongsTo { return $this->belongsTo(Account::class); }
    public function store(): BelongsTo { return $this->belongsTo(Store::class); }
    public function shipment(): BelongsTo { return $this->belongsTo(Shipment::class); }
    public function items(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Check whether this order can have a shipment created for it.
     * Ready/pending orders that haven't been shipped yet are shippable.
     */
    public function isShippable(): bool
    {
        return in_array($this->status, [self::STATUS_PENDING, self::STATUS_READY], true);
    }

    /**
     * Check whether this order already has an associated shipment.
     */
    public function hasShipment(): bool
    {
        return $this->shipment_id !== null;
    }

    public function isShipped(): bool
    {
        return in_array($this->status, [self::STATUS_SHIPPED, self::STATUS_DELIVERED], true);
    }

    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }
}
