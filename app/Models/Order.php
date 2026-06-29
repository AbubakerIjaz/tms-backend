<?php

namespace App\Models;

use App\Traits\BelongsToShop;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use BelongsToShop;

    protected $fillable = [
        'shop_id',
        'client_id',
        'design_id',
        'garment_type_id',
        'order_number',
        'status',
        'total_amount',
        'paid_amount',
        'payment_status',
        'order_date',
        'due_date',
        'delivery_date',
        'measurements_snapshot',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'total_amount' => 'decimal:2',
            'paid_amount' => 'decimal:2',
            'order_date' => 'date',
            'due_date' => 'date',
            'delivery_date' => 'date',
            'measurements_snapshot' => 'array',
        ];
    }

    protected $appends = ['balance'];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function design(): BelongsTo
    {
        return $this->belongsTo(Design::class);
    }

    public function garmentType(): BelongsTo
    {
        return $this->belongsTo(GarmentType::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(OrderImage::class)->orderBy('sort_order');
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class)->orderBy('sort_order');
    }

    public function getBalanceAttribute(): float
    {
        return max(0, (float) $this->total_amount - (float) $this->paid_amount);
    }

    public static function syncPaymentStatus(float $total, float $paid, ?string $status = null): string
    {
        if ($status === 'paid' || $status === 'pending') {
            return $status;
        }

        return ($total > 0 && $paid >= $total) ? 'paid' : 'pending';
    }
}
