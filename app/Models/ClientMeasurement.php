<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClientMeasurement extends Model
{
    protected $fillable = [
        'client_id',
        'garment_type_id',
        'label',
        'measurements',
        'notes',
        'measured_at',
    ];

    protected function casts(): array
    {
        return [
            'measurements' => 'array',
            'measured_at' => 'date',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function garmentType(): BelongsTo
    {
        return $this->belongsTo(GarmentType::class);
    }
}
