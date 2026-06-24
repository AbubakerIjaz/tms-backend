<?php

namespace App\Models;

use App\Traits\BelongsToShop;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StitchingSize extends Model
{
    use BelongsToShop;

    /** Optional starter presets — tailors can use their own field names. */
    public const SIZE_PRESETS = [
        'S' => [
            ['name' => 'Kameez', 'measurements' => [
                'Length' => 40.5, 'Shoulder' => 18, 'Chest' => 22.5, 'Waist' => 22.5,
                'Bottom' => 23.5, 'Sleeves' => 24, 'Arm Hole' => 10, 'Cuff' => 9.5, 'Collar Size' => 14,
            ]],
            ['name' => 'Shalwar', 'measurements' => [
                'Length' => 41, 'Bottom' => 7.5, 'In Seam' => 16,
            ]],
        ],
        'M' => [
            ['name' => 'Kameez', 'measurements' => [
                'Length' => 42, 'Shoulder' => 19, 'Chest' => 24, 'Waist' => 24,
                'Bottom' => 25, 'Sleeves' => 24.5, 'Arm Hole' => 11, 'Cuff' => 10, 'Collar Size' => 15,
            ]],
            ['name' => 'Shalwar', 'measurements' => [
                'Length' => 42, 'Bottom' => 8, 'In Seam' => 17,
            ]],
        ],
        'L' => [
            ['name' => 'Kameez', 'measurements' => [
                'Length' => 43, 'Shoulder' => 19.5, 'Chest' => 25, 'Waist' => 25,
                'Bottom' => 26, 'Sleeves' => 25, 'Arm Hole' => 11.5, 'Cuff' => 10, 'Collar Size' => 16,
            ]],
            ['name' => 'Shalwar', 'measurements' => [
                'Length' => 43, 'Bottom' => 8.5, 'In Seam' => 17.5,
            ]],
        ],
        'XL' => [
            ['name' => 'Kameez', 'measurements' => [
                'Length' => 44, 'Shoulder' => 20, 'Chest' => 26, 'Waist' => 26,
                'Bottom' => 27, 'Sleeves' => 25.5, 'Arm Hole' => 12, 'Cuff' => 10.5, 'Collar Size' => 17,
            ]],
            ['name' => 'Shalwar', 'measurements' => [
                'Length' => 44, 'Bottom' => 8.5, 'In Seam' => 18,
            ]],
        ],
    ];

    protected $fillable = [
        'shop_id',
        'client_id',
        'label',
        'standard_size',
        'sections',
        'notes',
        'measured_at',
    ];

    protected function casts(): array
    {
        return [
            'sections' => 'array',
            'measured_at' => 'date',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }
}
