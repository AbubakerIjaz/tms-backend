<?php

namespace App\Http\Controllers\Api;

use App\Models\Shop;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SettingsController extends ShopController
{
    public function show(Request $request): JsonResponse
    {
        $shop = Shop::findOrFail($this->shopId($request));

        return response()->json([
            'id' => $shop->id,
            'name' => $shop->name,
            'slug' => $shop->slug,
            'type' => $shop->type,
            'logo_url' => $shop->logo_url,
            'phone' => $shop->phone,
            'email' => $shop->email,
            'address' => $shop->address,
            'city' => $shop->city,
            'currency' => $shop->currency,
            'measurement_unit' => $shop->measurement_unit,
            'settings' => $shop->settings ?? [],
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $shop = Shop::findOrFail($this->shopId($request));

        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'type' => ['sometimes', 'in:tailor,boutique,both'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string'],
            'city' => ['nullable', 'string', 'max:100'],
            'currency' => ['sometimes', 'string', 'max:10'],
            'measurement_unit' => ['sometimes', 'in:inch,cm'],
            'settings' => ['nullable', 'array'],
            'logo' => ['nullable', 'image', 'max:2048'],
        ]);

        if ($request->hasFile('logo')) {
            if ($shop->logo_path) {
                Storage::disk('public')->delete($shop->logo_path);
            }
            $data['logo_path'] = $request->file('logo')->store('logos', 'public');
        }
        unset($data['logo']);

        if (isset($data['settings']) && is_array($data['settings'])) {
            $data['settings'] = array_merge($shop->settings ?? [], $data['settings']);
        }

        $shop->update($data);

        return response()->json([
            'id' => $shop->id,
            'name' => $shop->name,
            'slug' => $shop->slug,
            'type' => $shop->type,
            'logo_url' => $shop->fresh()->logo_url,
            'phone' => $shop->phone,
            'email' => $shop->email,
            'address' => $shop->address,
            'city' => $shop->city,
            'currency' => $shop->currency,
            'measurement_unit' => $shop->measurement_unit,
            'settings' => $shop->settings ?? [],
        ]);
    }
}
