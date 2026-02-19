<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class Account extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'name',
        'type',
        'status',
        'kyc_status',
        'slug',
        'settings',
        // FR-IAM-008: Account Settings
        'language',
        'currency',
        'timezone',
        'country',
        'contact_phone',
        'contact_email',
        'address_line_1',
        'address_line_2',
        'city',
        'postal_code',
        'date_format',
        'weight_unit',
        'dimension_unit',
    ];

    protected $casts = [
        'settings' => 'array',
    ];

    protected $attributes = [
        'type'           => 'individual',
        'status'         => 'pending',
        'kyc_status'     => 'unverified',
        'language'       => 'ar',
        'currency'       => 'SAR',
        'timezone'       => 'Asia/Riyadh',
        'country'        => 'SA',
        'date_format'    => 'Y-m-d',
        'weight_unit'    => 'kg',
        'dimension_unit' => 'cm',
    ];

    // ─── Relationships ────────────────────────────────────────────

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function owner(): HasMany
    {
        return $this->hasMany(User::class)->where('is_owner', true);
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }

    public function roles(): HasMany
    {
        return $this->hasMany(Role::class);
    }

    public function organizationProfile(): HasOne
    {
        return $this->hasOne(OrganizationProfile::class);
    }

    public function kycVerification(): HasOne
    {
        return $this->hasOne(KycVerification::class)->latestOfMany();
    }

    public function kycVerifications(): HasMany
    {
        return $this->hasMany(KycVerification::class);
    }

    public function invitations(): HasMany
    {
        return $this->hasMany(Invitation::class);
    }

    public function stores(): HasMany
    {
        return $this->hasMany(Store::class);
    }

    public function wallet(): HasOne
    {
        return $this->hasOne(Wallet::class);
    }

    public function paymentMethods(): HasMany
    {
        return $this->hasMany(PaymentMethod::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function shipments(): HasMany
    {
        return $this->hasMany(Shipment::class);
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(Address::class);
    }

    public function rateQuotes(): HasMany
    {
        return $this->hasMany(RateQuote::class);
    }

    public function pricingRules(): HasMany
    {
        return $this->hasMany(PricingRule::class);
    }

    // ─── Helpers ──────────────────────────────────────────────────

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isOrganization(): bool
    {
        return $this->type === 'organization';
    }

    public function isIndividual(): bool
    {
        return $this->type === 'individual';
    }

    public function isKycApproved(): bool
    {
        return $this->kyc_status === 'approved';
    }

    /**
     * Check if account has been actively used (has shipments, transactions, etc.)
     * Used to determine if account type can be changed.
     */
    public function hasActiveUsage(): bool
    {
        // For now: check if account has more than the owner user
        // Future: check shipments, transactions, invoices, etc.
        return $this->users()->where('is_owner', false)->exists();
    }

    /**
     * Generate a unique slug from the account name.
     */
    public static function generateSlug(string $name): string
    {
        $slug = Str::slug($name);
        $original = $slug;
        $counter = 1;

        while (static::where('slug', $slug)->exists()) {
            $slug = $original . '-' . $counter++;
        }

        return $slug;
    }

    // ─── FR-IAM-008: Settings Constants ──────────────────────────

    public const SUPPORTED_LANGUAGES = ['ar', 'en', 'fr', 'tr', 'ur'];

    public const SUPPORTED_CURRENCIES = [
        'SAR', 'AED', 'USD', 'EUR', 'GBP', 'EGP', 'KWD', 'BHD', 'OMR', 'QAR', 'JOD', 'TRY',
    ];

    public const SUPPORTED_TIMEZONES = [
        'Asia/Riyadh', 'Asia/Dubai', 'Asia/Kuwait', 'Asia/Bahrain', 'Asia/Qatar',
        'Asia/Muscat', 'Asia/Amman', 'Africa/Cairo', 'Europe/Istanbul',
        'Europe/London', 'America/New_York', 'UTC',
    ];

    public const SUPPORTED_COUNTRIES = [
        'SA', 'AE', 'KW', 'BH', 'QA', 'OM', 'JO', 'EG', 'TR', 'US', 'GB',
    ];

    public const SUPPORTED_DATE_FORMATS = [
        'Y-m-d', 'd/m/Y', 'm/d/Y', 'd-m-Y', 'd.m.Y',
    ];

    public const SUPPORTED_WEIGHT_UNITS = ['kg', 'lb'];
    public const SUPPORTED_DIMENSION_UNITS = ['cm', 'in'];

    /**
     * Get a specific extended setting from the JSONB settings column.
     */
    public function getSetting(string $key, mixed $default = null): mixed
    {
        return data_get($this->settings, $key, $default);
    }

    /**
     * Set a specific extended setting in the JSONB settings column.
     */
    public function setSetting(string $key, mixed $value): self
    {
        $settings = $this->settings ?? [];
        data_set($settings, $key, $value);
        $this->settings = $settings;
        return $this;
    }

    /**
     * Get all settings (dedicated columns + JSONB extended).
     */
    public function allSettings(): array
    {
        return [
            'language'       => $this->language,
            'currency'       => $this->currency,
            'timezone'       => $this->timezone,
            'country'        => $this->country,
            'contact_phone'  => $this->contact_phone,
            'contact_email'  => $this->contact_email,
            'address'        => [
                'line_1'      => $this->address_line_1,
                'line_2'      => $this->address_line_2,
                'city'        => $this->city,
                'postal_code' => $this->postal_code,
                'country'     => $this->country,
            ],
            'date_format'    => $this->date_format,
            'weight_unit'    => $this->weight_unit,
            'dimension_unit' => $this->dimension_unit,
            'extended'       => $this->settings ?? [],
        ];
    }
}
