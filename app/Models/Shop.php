<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Shop extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'type',
        'logo_path',
        'phone',
        'email',
        'address',
        'city',
        'currency',
        'measurement_unit',
        'settings',
    ];

    protected function casts(): array
    {
        return [
            'settings' => 'array',
        ];
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function clients(): HasMany
    {
        return $this->hasMany(Client::class);
    }

    public function garmentTypes(): HasMany
    {
        return $this->hasMany(GarmentType::class);
    }

    public function designs(): HasMany
    {
        return $this->hasMany(Design::class);
    }

    public function categories(): HasMany
    {
        return $this->hasMany(Category::class);
    }

    public function galleryItems(): HasMany
    {
        return $this->hasMany(GalleryItem::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function stitchingSizes(): HasMany
    {
        return $this->hasMany(StitchingSize::class);
    }

    public function getLogoUrlAttribute(): ?string
    {
        if (! $this->logo_path) {
            return null;
        }

        if (str_starts_with($this->logo_path, 'http://') || str_starts_with($this->logo_path, 'https://')) {
            return $this->logo_path;
        }

        return Storage::disk('public')->url($this->logo_path);
    }
}
