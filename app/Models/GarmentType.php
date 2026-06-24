<?php

namespace App\Models;

use App\Traits\BelongsToShop;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GarmentType extends Model
{
    use BelongsToShop;

    protected $fillable = [
        'shop_id',
        'name',
        'description',
        'measurement_fields',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'measurement_fields' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function designs(): HasMany
    {
        return $this->hasMany(Design::class);
    }

    public function measurements(): HasMany
    {
        return $this->hasMany(ClientMeasurement::class);
    }
}
