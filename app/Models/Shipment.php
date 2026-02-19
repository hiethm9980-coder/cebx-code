<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Traits\BelongsToAccount;

/**
 * Shipment — FR-SH-001→019
 *
 * Core entity for the shipping gateway. Supports:
 * - Direct shipping (FR-SH-001)
 * - Order-to-shipment (FR-SH-002)
 * - Multi-parcel (FR-SH-003)
 * - State machine (FR-SH-006)
 * - Financial masking (FR-SH-011)
 */
class Shipment extends Model
{
    use HasUuids, HasFactory, SoftDeletes, BelongsToAccount;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'account_id', 'store_id', 'order_id', 'reference_number', 'source', 'status', 'status_reason',
        'carrier_code', 'carrier_name', 'service_code', 'service_name',
        'tracking_number', 'carrier_shipment_id', 'tracking_url',
        'tracking_status', 'tracking_updated_at',
        'sender_address_id', 'sender_name', 'sender_company', 'sender_phone', 'sender_email',
        'sender_address_1', 'sender_address_2', 'sender_city', 'sender_state', 'sender_postal_code', 'sender_country',
        'recipient_address_id', 'recipient_name', 'recipient_company', 'recipient_phone', 'recipient_email',
        'recipient_address_1', 'recipient_address_2', 'recipient_city', 'recipient_state', 'recipient_postal_code', 'recipient_country',
        'shipping_rate', 'insurance_amount', 'cod_amount', 'total_charge', 'platform_fee', 'profit_margin', 'currency',
        'total_weight', 'volumetric_weight', 'chargeable_weight', 'parcels_count',
        'is_international', 'is_cod', 'is_insured', 'is_return', 'has_dangerous_goods',
        'dg_declaration_status', 'kyc_verified',
        'label_url', 'label_format', 'label_print_count', 'label_created_at',
        'balance_reservation_id', 'reserved_amount',
        'delivery_instructions', 'estimated_delivery_at', 'actual_delivery_at', 'picked_up_at',
        'created_by', 'cancelled_by', 'cancellation_reason',
        'debit_ledger_entry_id', 'refund_ledger_entry_id',
        'rule_evaluation_log', 'metadata',
    ];

    protected $casts = [
        'shipping_rate'        => 'decimal:2',
        'insurance_amount'     => 'decimal:2',
        'cod_amount'           => 'decimal:2',
        'total_charge'         => 'decimal:2',
        'platform_fee'         => 'decimal:2',
        'profit_margin'        => 'decimal:2',
        'total_weight'         => 'decimal:3',
        'volumetric_weight'    => 'decimal:3',
        'chargeable_weight'    => 'decimal:3',
        'parcels_count'        => 'integer',
        'label_print_count'    => 'integer',
        'is_international'     => 'boolean',
        'is_cod'               => 'boolean',
        'is_insured'           => 'boolean',
        'is_return'            => 'boolean',
        'has_dangerous_goods'  => 'boolean',
        'kyc_verified'         => 'boolean',
        'reserved_amount'      => 'decimal:2',
        'estimated_delivery_at' => 'datetime',
        'actual_delivery_at'   => 'datetime',
        'picked_up_at'         => 'datetime',
        'label_created_at'     => 'datetime',
        'rule_evaluation_log'  => 'array',
        'metadata'             => 'array',
        'tracking_updated_at'  => 'datetime',
    ];

    // Financial fields hidden from non-financial roles (FR-SH-011)
    protected $hidden = ['profit_margin'];

    // ─── Status Constants ────────────────────────────────────────

    public const STATUS_DRAFT            = 'draft';
    public const STATUS_VALIDATED        = 'validated';
    public const STATUS_RATED            = 'rated';
    public const STATUS_PAYMENT_PENDING  = 'payment_pending';
    public const STATUS_PURCHASED        = 'purchased';
    public const STATUS_READY_FOR_PICKUP = 'ready_for_pickup';
    public const STATUS_PICKED_UP        = 'picked_up';
    public const STATUS_IN_TRANSIT       = 'in_transit';
    public const STATUS_OUT_FOR_DELIVERY = 'out_for_delivery';
    public const STATUS_DELIVERED        = 'delivered';
    public const STATUS_RETURNED         = 'returned';
    public const STATUS_EXCEPTION        = 'exception';
    public const STATUS_CANCELLED        = 'cancelled';
    public const STATUS_FAILED           = 'failed';

    // FR-SH-006: Allowed state transitions
    public const TRANSITIONS = [
        self::STATUS_DRAFT            => [self::STATUS_VALIDATED, self::STATUS_CANCELLED, self::STATUS_FAILED],
        self::STATUS_VALIDATED        => [self::STATUS_RATED, self::STATUS_CANCELLED, self::STATUS_FAILED],
        self::STATUS_RATED            => [self::STATUS_PAYMENT_PENDING, self::STATUS_PURCHASED, self::STATUS_CANCELLED],
        self::STATUS_PAYMENT_PENDING  => [self::STATUS_PURCHASED, self::STATUS_CANCELLED, self::STATUS_FAILED],
        self::STATUS_PURCHASED        => [self::STATUS_READY_FOR_PICKUP, self::STATUS_CANCELLED],
        self::STATUS_READY_FOR_PICKUP => [self::STATUS_PICKED_UP, self::STATUS_CANCELLED],
        self::STATUS_PICKED_UP        => [self::STATUS_IN_TRANSIT, self::STATUS_EXCEPTION, self::STATUS_RETURNED],
        self::STATUS_IN_TRANSIT       => [self::STATUS_OUT_FOR_DELIVERY, self::STATUS_DELIVERED, self::STATUS_EXCEPTION, self::STATUS_RETURNED],
        self::STATUS_OUT_FOR_DELIVERY => [self::STATUS_DELIVERED, self::STATUS_EXCEPTION, self::STATUS_RETURNED],
        self::STATUS_EXCEPTION        => [self::STATUS_IN_TRANSIT, self::STATUS_RETURNED, self::STATUS_DELIVERED],
        self::STATUS_DELIVERED        => [],
        self::STATUS_RETURNED         => [],
        self::STATUS_CANCELLED        => [],
        self::STATUS_FAILED           => [self::STATUS_DRAFT],
    ];

    // Statuses where cancellation is allowed (FR-SH-007)
    public const CANCELLABLE = [
        self::STATUS_DRAFT, self::STATUS_VALIDATED, self::STATUS_RATED,
        self::STATUS_PAYMENT_PENDING, self::STATUS_PURCHASED, self::STATUS_READY_FOR_PICKUP,
    ];

    // Statuses where label exists (FR-SH-008)
    public const HAS_LABEL = [
        self::STATUS_PURCHASED, self::STATUS_READY_FOR_PICKUP, self::STATUS_PICKED_UP,
        self::STATUS_IN_TRANSIT, self::STATUS_OUT_FOR_DELIVERY, self::STATUS_DELIVERED,
    ];

    // ─── Source Constants ────────────────────────────────────────

    public const SOURCE_DIRECT = 'direct';
    public const SOURCE_ORDER  = 'order';
    public const SOURCE_BULK   = 'bulk';
    public const SOURCE_RETURN = 'return';

    // ─── Relationships ───────────────────────────────────────────

    public function account(): BelongsTo    { return $this->belongsTo(Account::class); }
    public function store(): BelongsTo      { return $this->belongsTo(Store::class); }
    public function order(): BelongsTo      { return $this->belongsTo(Order::class); }
    public function creator(): BelongsTo    { return $this->belongsTo(User::class, 'created_by'); }
    public function canceller(): BelongsTo  { return $this->belongsTo(User::class, 'cancelled_by'); }

    public function parcels(): HasMany
    {
        return $this->hasMany(Parcel::class)->orderBy('sequence');
    }

    public function statusHistory(): HasMany
    {
        return $this->hasMany(ShipmentStatusHistory::class)->orderBy('created_at');
    }

    public function rateQuotes(): HasMany
    {
        return $this->hasMany(RateQuote::class)->orderByDesc('created_at');
    }

    public function latestQuote()
    {
        return $this->hasOne(RateQuote::class)->where('status', 'completed')->latestOfMany();
    }

    public function carrierShipment(): HasOne
    {
        return $this->hasOne(CarrierShipment::class)->latestOfMany();
    }

    public function carrierDocuments(): HasMany
    {
        return $this->hasMany(CarrierDocument::class);
    }

    public function carrierErrors(): HasMany
    {
        return $this->hasMany(CarrierError::class);
    }

    public function trackingEvents(): HasMany
    {
        return $this->hasMany(TrackingEvent::class)->orderBy('event_time', 'desc');
    }

    public function exceptions(): HasMany
    {
        return $this->hasMany(ShipmentException::class);
    }

    public function trackingSubscriptions(): HasMany
    {
        return $this->hasMany(TrackingSubscription::class);
    }

    public function senderAddress(): BelongsTo
    {
        return $this->belongsTo(Address::class, 'sender_address_id');
    }

    public function recipientAddress(): BelongsTo
    {
        return $this->belongsTo(Address::class, 'recipient_address_id');
    }

    // ─── State Machine Helpers ───────────────────────────────────

    public function canTransitionTo(string $newStatus): bool
    {
        return in_array($newStatus, self::TRANSITIONS[$this->status] ?? []);
    }

    public function isCancellable(): bool
    {
        return in_array($this->status, self::CANCELLABLE);
    }

    public function hasLabel(): bool
    {
        return in_array($this->status, self::HAS_LABEL) && !empty($this->label_url);
    }

    public function isDraft(): bool      { return $this->status === self::STATUS_DRAFT; }
    public function isPurchased(): bool  { return $this->status === self::STATUS_PURCHASED; }
    public function isDelivered(): bool  { return $this->status === self::STATUS_DELIVERED; }
    public function isCancelled(): bool  { return $this->status === self::STATUS_CANCELLED; }
    public function isActive(): bool     { return !in_array($this->status, [self::STATUS_CANCELLED, self::STATUS_FAILED, self::STATUS_DELIVERED, self::STATUS_RETURNED]); }

    // ─── Display ─────────────────────────────────────────────────

    public function statusDisplay(): array
    {
        return match ($this->status) {
            self::STATUS_DRAFT            => ['label' => 'مسودة', 'color' => 'gray'],
            self::STATUS_VALIDATED        => ['label' => 'تم التحقق', 'color' => 'blue'],
            self::STATUS_RATED            => ['label' => 'مسعّر', 'color' => 'indigo'],
            self::STATUS_PAYMENT_PENDING  => ['label' => 'بانتظار الدفع', 'color' => 'yellow'],
            self::STATUS_PURCHASED        => ['label' => 'تم الشراء', 'color' => 'teal'],
            self::STATUS_READY_FOR_PICKUP => ['label' => 'جاهز للاستلام', 'color' => 'cyan'],
            self::STATUS_PICKED_UP        => ['label' => 'تم الاستلام', 'color' => 'blue'],
            self::STATUS_IN_TRANSIT       => ['label' => 'قيد الشحن', 'color' => 'orange'],
            self::STATUS_OUT_FOR_DELIVERY => ['label' => 'خارج للتسليم', 'color' => 'amber'],
            self::STATUS_DELIVERED        => ['label' => 'تم التسليم', 'color' => 'green'],
            self::STATUS_RETURNED         => ['label' => 'مرتجع', 'color' => 'red'],
            self::STATUS_EXCEPTION        => ['label' => 'استثناء', 'color' => 'red'],
            self::STATUS_CANCELLED        => ['label' => 'ملغى', 'color' => 'gray'],
            self::STATUS_FAILED           => ['label' => 'فشل', 'color' => 'red'],
            default                       => ['label' => $this->status, 'color' => 'gray'],
        };
    }

    // ─── Weight Helpers ──────────────────────────────────────────

    public function recalculateWeights(int $dimDivisor = 5000): void
    {
        $parcels = $this->parcels;
        $totalActual = $parcels->sum('weight');
        $totalVolumetric = $parcels->sum(function ($p) use ($dimDivisor) {
            if ($p->length && $p->width && $p->height) {
                return ($p->length * $p->width * $p->height) / $dimDivisor;
            }
            return $p->weight;
        });

        $this->update([
            'total_weight'      => $totalActual,
            'volumetric_weight' => round($totalVolumetric, 3),
            'chargeable_weight' => max($totalActual, $totalVolumetric),
            'parcels_count'     => $parcels->count(),
        ]);
    }

    // ─── Scopes ──────────────────────────────────────────────────

    public function scopeActive($q)          { return $q->whereNotIn('status', [self::STATUS_CANCELLED, self::STATUS_FAILED]); }
    public function scopeForStore($q, $id)   { return $q->where('store_id', $id); }
    public function scopeWithStatus($q, $s)  { return $q->where('status', $s); }
    public function scopeCancellable($q)     { return $q->whereIn('status', self::CANCELLABLE); }

    // ─── Reference Number Generator ──────────────────────────────

    public static function generateReference(): string
    {
        do {
            $ref = 'SHP-' . strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 8));
        } while (static::where('reference_number', $ref)->exists());
        return $ref;
    }
}
