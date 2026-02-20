<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
class Vessel extends Model {
    protected $guarded = [];
    public function containers(): HasMany { return $this->hasMany(Container::class); }
    public function schedules(): HasMany { return $this->hasMany(Schedule::class); }
}
