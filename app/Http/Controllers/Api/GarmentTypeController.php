<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\AppliesListingFilters;
use App\Models\GarmentType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GarmentTypeController extends ShopController
{
    use AppliesListingFilters;

    public function index(Request $request): JsonResponse
    {
        $query = GarmentType::forShop($this->shopId($request))->orderBy('name');

        $search = $this->searchTerm($request);
        $this->applyColumnSearch($query, $search, ['name', 'description']);

        $this->applyDateRangeFilter($query, $request, 'created_at');

        if (! $request->has('page')) {
            return response()->json($query->get());
        }

        return response()->json($query->paginate($this->listingPerPage($request)));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'measurement_fields' => ['nullable', 'array'],
            'measurement_fields.*.key' => ['required_with:measurement_fields', 'string'],
            'measurement_fields.*.label' => ['required_with:measurement_fields', 'string'],
            'is_active' => ['boolean'],
        ]);

        $type = GarmentType::create([
            ...$data,
            'shop_id' => $this->shopId($request),
        ]);

        return response()->json($type, 201);
    }

    public function update(Request $request, GarmentType $garmentType): JsonResponse
    {
        $this->authorize($request, $garmentType);

        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'measurement_fields' => ['nullable', 'array'],
            'measurement_fields.*.key' => ['required_with:measurement_fields', 'string'],
            'measurement_fields.*.label' => ['required_with:measurement_fields', 'string'],
            'is_active' => ['boolean'],
        ]);

        $garmentType->update($data);

        return response()->json($garmentType);
    }

    public function destroy(Request $request, GarmentType $garmentType): JsonResponse
    {
        $this->authorize($request, $garmentType);
        $garmentType->delete();

        return response()->json(['message' => 'Garment type deleted.']);
    }

    private function authorize(Request $request, GarmentType $garmentType): void
    {
        abort_if($garmentType->shop_id !== $this->shopId($request), 404);
    }
}
