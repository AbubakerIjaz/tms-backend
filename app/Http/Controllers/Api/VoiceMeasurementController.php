<?php

namespace App\Http\Controllers\Api;

use App\Models\Client;
use App\Models\StitchingSize;
use App\Support\MeasurementHelper;
use App\Support\ShopSettings;
use App\Support\VoiceMeasurementParser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class VoiceMeasurementController extends ShopController
{
    public function parse(Request $request): JsonResponse
    {
        $this->ensureModuleEnabled($request);

        $data = $request->validate([
            'text' => ['required', 'string', 'max:5000'],
        ]);

        $parsed = VoiceMeasurementParser::parse($data['text']);
        $shopId = $this->shopId($request);

        $clients = [];
        if ($parsed['client_hint']) {
            $clients = Client::forShop($shopId)
                ->where('name', 'ilike', '%'.$parsed['client_hint'].'%')
                ->limit(5)
                ->get(['id', 'name', 'phone']);
        }

        return response()->json([
            ...$parsed,
            'matched_clients' => $clients,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $this->ensureModuleEnabled($request);

        $data = $request->validate([
            'client_id' => ['required', 'exists:clients,id'],
            'text' => ['nullable', 'string', 'max:5000'],
            'label' => ['nullable', 'string', 'max:255'],
            'standard_size' => ['nullable', 'in:S,M,L,XL'],
            'sections' => ['required', 'array', 'min:1'],
            'sections.*.name' => ['nullable', 'string', 'max:100'],
            'sections.*.measurements' => ['required', 'array'],
            'notes' => ['nullable', 'string'],
            'measured_at' => ['nullable', 'date'],
        ]);

        $shopId = $this->shopId($request);
        abort_unless(
            Client::forShop($shopId)->whereKey($data['client_id'])->exists(),
            422,
            'Client not found.'
        );

        $sections = MeasurementHelper::normalizeSections(
            collect($data['sections'])
                ->map(fn ($section) => [
                    'name' => $section['name'] ?? 'Measurements',
                    'measurements' => $section['measurements'] ?? [],
                ])
                ->all()
        );

        if (empty($sections)) {
            throw ValidationException::withMessages([
                'sections' => ['At least one measurement field with a name and value is required.'],
            ]);
        }

        $record = StitchingSize::create([
            'shop_id' => $shopId,
            'client_id' => $data['client_id'],
            'label' => $data['label'] ?? ($data['text'] ? 'Voice measurement' : null),
            'standard_size' => $data['standard_size'] ?? null,
            'sections' => $sections,
            'notes' => $data['notes'] ?? ($data['text'] ?? null),
            'measured_at' => $data['measured_at'] ?? now()->toDateString(),
        ]);

        return response()->json($record->load('client:id,name,phone'), 201);
    }

    private function ensureModuleEnabled(Request $request): void
    {
        $shop = $request->user()->shop;
        abort_unless($shop, 403);

        if (! ShopSettings::moduleEnabled($shop, 'module_voice_measurements')) {
            throw ValidationException::withMessages([
                'module' => ['Voice measurements module is disabled in settings.'],
            ]);
        }
    }
}
