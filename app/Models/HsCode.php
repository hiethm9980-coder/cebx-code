<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class HsCode extends Model
{
    use HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'code', 'chapter', 'heading', 'subheading', 'description', 'description_ar',
        'country', 'duty_rate', 'vat_rate', 'excise_rate',
        'is_restricted', 'is_prohibited', 'requires_license', 'is_dangerous_goods',
        'restriction_notes', 'unit_of_measure', 'is_active',
    ];

    protected $casts = [
        'duty_rate' => 'decimal:4',
        'vat_rate' => 'decimal:4',
        'excise_rate' => 'decimal:4',
        'is_restricted' => 'boolean',
        'is_prohibited' => 'boolean',
        'requires_license' => 'boolean',
        'is_dangerous_goods' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function scopeActive($q) { return $q->where('is_active', true); }
    public function scopeForCountry($q, $c) { return $q->where(fn($q) => $q->where('country', $c)->orWhere('country', '*')); }
    public function scopeRestricted($q) { return $q->where('is_restricted', true); }

    public function calculateDuty(float $value): array
    {
        return [
            'duty' => round($value * ($this->duty_rate / 100), 2),
            'vat' => round(($value + ($value * $this->duty_rate / 100)) * ($this->vat_rate / 100), 2),
            'excise' => round($value * ($this->excise_rate / 100), 2),
        ];
    }
}
