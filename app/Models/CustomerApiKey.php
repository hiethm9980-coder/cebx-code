<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;

class CustomerApiKey extends Model
{
    use HasUuids, BelongsToAccount, SoftDeletes;
    protected $guarded = ['id'];
    protected $casts = ['scopes' => 'json', 'last_used_at' => 'datetime', 'expires_at' => 'datetime'];
    protected $hidden = ['key_hash'];
}
