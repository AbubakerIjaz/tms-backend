<?php

namespace App\Models;

use App\Traits\BelongsToShop;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Client extends Model
{
    use BelongsToShop;

    protected $fillable = [
        'shop_id',
        'name',
        'phone',
        'email',
        'address',
        'gender',
        'notes',
    ];

    public function measurements(): HasMany
    {
        return $this->hasMany(ClientMeasurement::class)->latest('measured_at');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class)->latest();
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function stitchingSizes(): HasMany
    {
        return $this->hasMany(StitchingSize::class)->latest('measured_at');
    }
}
