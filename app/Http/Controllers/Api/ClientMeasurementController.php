<?php

namespace App\Http\Controllers\Api;

use App\Models\Client;
use App\Models\ClientMeasurement;
use App\Support\MeasurementHelper;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ClientMeasurementController extends ShopController
{
    public function index(Request $request, Client $client): JsonResponse
    {
        $this->authorizeClient($request, $client);

        $measurements = $client->measurements()->with('garmentType:id,name')->get();

        return response()->json($measurements);
    }

    public function store(Request $request, Client $client): JsonResponse
    {
        $this->authorizeClient($request, $client);

        $data = $request->validate([
            'garment_type_id' => ['nullable', 'exists:garment_types,id'],
            'label' => ['nullable', 'string', 'max:255'],
            'measurements' => ['required', 'array'],
            'notes' => ['nullable', 'string'],
            'measured_at' => ['required', 'date'],
        ]);

        $data['measurements'] = MeasurementHelper::normalize($data['measurements']);

        if (empty($data['measurements'])) {
            return response()->json(['message' => 'At least one measurement field is required.'], 422);
        }

        $measurement = $client->measurements()->create($data);

        return response()->json($measurement->load('garmentType:id,name'), 201);
    }

    public function update(Request $request, Client $client, ClientMeasurement $measurement): JsonResponse
    {
        $this->authorizeClient($request, $client);
        abort_if($measurement->client_id !== $client->id, 404);

        $data = $request->validate([
            'garment_type_id' => ['nullable', 'exists:garment_types,id'],
            'label' => ['nullable', 'string', 'max:255'],
            'measurements' => ['sometimes', 'array'],
            'notes' => ['nullable', 'string'],
            'measured_at' => ['sometimes', 'date'],
        ]);

        if (isset($data['measurements'])) {
            $data['measurements'] = MeasurementHelper::normalize($data['measurements']);

            if (empty($data['measurements'])) {
                return response()->json(['message' => 'At least one measurement field is required.'], 422);
            }
        }

        $measurement->update($data);

        return response()->json($measurement->load('garmentType:id,name'));
    }

    public function destroy(Request $request, Client $client, ClientMeasurement $measurement): JsonResponse
    {
        $this->authorizeClient($request, $client);
        abort_if($measurement->client_id !== $client->id, 404);
        $measurement->delete();

        return response()->json(['message' => 'Measurement deleted.']);
    }

    private function authorizeClient(Request $request, Client $client): void
    {
        abort_if($client->shop_id !== $this->shopId($request), 404);
    }
}
