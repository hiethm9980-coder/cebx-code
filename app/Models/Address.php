<?php
namespace App\Models;
use App\Models\Traits\BelongsToAccount;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class Address extends Model {
    use HasFactory, HasUuids, BelongsToAccount, SoftDeletes;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $guarded = [];

    protected $casts = [
        'is_default_sender' => 'boolean',
    ];

    public function scopeDefaultSender($query)
    {
        return $query->where('is_default_sender', true)->where('type', '!=', 'recipient');
    }
}
