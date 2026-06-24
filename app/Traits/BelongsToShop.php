<?php

namespace App\Traits;

use App\Models\Shop;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToShop
{
    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    public function scopeForShop(Builder $query, int $shopId): Builder
    {
        return $query->where($this->getTable().'.shop_id', $shopId);
    }
}
