<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Schema;

class Account extends Model
{
    use HasFactory, HasUuids;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $guarded = [];

    protected $casts = [
        'settings' => 'array',
        'metadata' => 'array',
    ];

    /**
     * Generate a URL-safe unique slug for an account name.
     */
    public static function generateSlug(string $name): string
    {
        $base = \Illuminate\Support\Str::slug($name);
        if (!static::where('slug', $base)->exists()) {
            return $base;
        }
        $i = 1;
        while (static::where('slug', "{$base}-{$i}")->exists()) {
            $i++;
        }
        return "{$base}-{$i}";
    }

    public function users(): HasMany { return $this->hasMany(User::class); }
    public function shipments(): HasMany { return $this->hasMany(Shipment::class); }
    public function orders(): HasMany { return $this->hasMany(Order::class); }
    public function stores(): HasMany { return $this->hasMany(Store::class); }
    public function wallet(): HasOne { return $this->hasOne(Wallet::class); }
    public function addresses(): HasMany { return $this->hasMany(Address::class); }
    public function tickets(): HasMany { return $this->hasMany(SupportTicket::class); }
    public function notifications(): HasMany { return $this->hasMany(Notification::class); }
    public function invitations(): HasMany { return $this->hasMany(Invitation::class); }
    public function claims(): HasMany { return $this->hasMany(Claim::class); }
    public function kycRequests(): HasMany { return $this->hasMany(KycRequest::class); }
    public function organizationProfile(): HasOne { return $this->hasOne(OrganizationProfile::class); }
    public function kycVerification(): HasOne { return $this->hasOne(KycVerification::class)->latestOfMany(); }
    public function kycVerifications(): HasMany { return $this->hasMany(KycVerification::class); }

    // ─── Settings Constants ───────────────────────────────────────

    public const SUPPORTED_LANGUAGES = ['ar', 'en', 'fr', 'tr', 'ur'];

    public const SUPPORTED_CURRENCIES = ['SAR', 'AED', 'USD', 'EUR', 'GBP', 'EGP', 'KWD', 'BHD', 'OMR', 'QAR', 'JOD', 'TRY'];

    public const SUPPORTED_TIMEZONES = [
        'Asia/Riyadh', 'Asia/Dubai', 'Asia/Kuwait', 'Asia/Bahrain', 'Asia/Qatar',
        'Asia/Muscat', 'Asia/Amman', 'Africa/Cairo', 'Europe/Istanbul',
        'America/New_York', 'America/Chicago', 'America/Los_Angeles',
        'Europe/London', 'Europe/Paris', 'UTC',
    ];

    public const SUPPORTED_COUNTRIES = ['SA', 'AE', 'KW', 'BH', 'QA', 'OM', 'JO', 'EG', 'TR', 'US', 'GB'];

    public const SUPPORTED_DATE_FORMATS = ['Y-m-d', 'd/m/Y', 'm/d/Y', 'd-m-Y'];

    public const SUPPORTED_WEIGHT_UNITS = ['kg', 'lb', 'g', 'oz'];

    public const SUPPORTED_DIMENSION_UNITS = ['cm', 'in', 'mm'];

    /**
     * Return all account settings as a flat array with defaults merged.
     * Combines dedicated DB columns with the JSON 'settings' column (extended).
     */
    public function allSettings(): array
    {
        return [
            'name'            => $this->name ?? null,
            'language'        => $this->language ?? 'ar',
            'currency'        => $this->currency ?? 'SAR',
            'timezone'        => $this->timezone ?? 'Asia/Riyadh',
            'country'         => $this->country ?? 'SA',
            'contact_phone'   => $this->contact_phone ?? null,
            'contact_email'   => $this->contact_email ?? null,
            'date_format'     => $this->date_format ?? 'Y-m-d',
            'weight_unit'     => $this->weight_unit ?? 'kg',
            'dimension_unit'  => $this->dimension_unit ?? 'cm',
            'address'         => [
                'line_1'      => $this->address_line_1 ?? null,
                'line_2'      => $this->address_line_2 ?? null,
                'city'        => $this->city ?? null,
                'postal_code' => $this->postal_code ?? null,
            ],
            'extended'        => $this->settings ?? [],
        ];
    }

    public function isIndividual(): bool
    {
        return ($this->type ?? 'individual') === 'individual';
    }

    public function isOrganization(): bool
    {
        return ($this->type ?? '') === 'organization';
    }

    public function allowsTeamManagement(): bool
    {
        return $this->isOrganization();
    }

    public function externalUserCount(): int
    {
        $query = $this->users()->withoutGlobalScopes();

        if (Schema::hasColumn('users', 'user_type')) {
            $query->where('user_type', 'external');
        }

        return $query->count();
    }

    public function hasActiveUsage(): bool
    {
        if ($this->externalUserCount() > 1) {
            return true;
        }

        foreach ([
            $this->shipments(),
            $this->orders(),
            $this->stores(),
            $this->invitations(),
            $this->claims(),
            $this->kycRequests(),
        ] as $relation) {
            if ($relation->exists()) {
                return true;
            }
        }

        return false;
    }
}
