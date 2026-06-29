<?php

namespace App\Models;

use App\Traits\BelongsToShop;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Design extends Model
{
    use BelongsToShop;

    protected $fillable = [
        'shop_id',
        'garment_type_id',
        'name',
        'description',
        'base_price',
        'image_path',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'base_price' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    protected $appends = ['image_url'];

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * @return array<string, \Closure>
     */
    public static function activeLinkCountQueries(): array
    {
        $activeOrder = fn ($q) => $q->whereNotIn('status', ['delivered', 'cancelled']);

        return [
            'orders as active_orders_count' => $activeOrder,
            'orderItems as active_order_items_count' => fn ($q) => $q
                ->whereNotNull('design_id')
                ->whereHas('order', $activeOrder),
        ];
    }

    public function isLinkedToOrders(): bool
    {
        return $this->orders()
            ->whereNotIn('status', ['delivered', 'cancelled'])
            ->exists()
            || $this->orderItems()
                ->whereNotNull('design_id')
                ->whereHas('order', fn ($q) => $q->whereNotIn('status', ['delivered', 'cancelled']))
                ->exists();
    }

    public function garmentType(): BelongsTo
    {
        return $this->belongsTo(GarmentType::class);
    }

    public function getImageUrlAttribute(): ?string
    {
        if (! $this->image_path) {
            return null;
        }

        if (str_starts_with($this->image_path, 'http://') || str_starts_with($this->image_path, 'https://')) {
            return $this->image_path;
        }

        return Storage::disk('public')->url($this->image_path);
    }
}
