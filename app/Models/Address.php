<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Traits\BelongsToAccount;

/**
 * Address — FR-SH-004: Address Book for sender/recipient.
 */
class Address extends Model
{
    use HasUuids, SoftDeletes, BelongsToAccount;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'account_id', 'type', 'is_default_sender', 'label',
        'contact_name', 'company_name', 'phone', 'email',
        'address_line_1', 'address_line_2', 'city', 'state',
        'postal_code', 'country', 'latitude', 'longitude', 'metadata',
    ];

    protected $casts = [
        'is_default_sender' => 'boolean',
        'latitude'          => 'decimal:7',
        'longitude'         => 'decimal:7',
        'metadata'          => 'array',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function toShipmentArray(string $prefix): array
    {
        return [
            "{$prefix}_name"        => $this->contact_name,
            "{$prefix}_company"     => $this->company_name,
            "{$prefix}_phone"       => $this->phone,
            "{$prefix}_email"       => $this->email,
            "{$prefix}_address_1"   => $this->address_line_1,
            "{$prefix}_address_2"   => $this->address_line_2,
            "{$prefix}_city"        => $this->city,
            "{$prefix}_state"       => $this->state,
            "{$prefix}_postal_code" => $this->postal_code,
            "{$prefix}_country"     => $this->country,
            "{$prefix}_address_id"  => $this->id,
        ];
    }

    public function scopeDefaultSender($q)
    {
        return $q->where('is_default_sender', true);
    }

    public function scopeSenders($q)
    {
        return $q->whereIn('type', ['sender', 'both']);
    }

    public function scopeRecipients($q)
    {
        return $q->whereIn('type', ['recipient', 'both']);
    }
}
