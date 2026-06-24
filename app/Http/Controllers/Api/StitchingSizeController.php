<?php

namespace App\Http\Controllers\Api;

use App\Models\StitchingSize;
use App\Support\MeasurementHelper;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class StitchingSizeController extends ShopController
{
    public function presets(): JsonResponse
    {
        return response()->json([
            'size_presets' => StitchingSize::SIZE_PRESETS,
            'example_sections' => [
                ['name' => 'Kameez', 'measurements' => ['Length' => '', 'Chest' => '', 'Waist' => '']],
                ['name' => 'Shalwar', 'measurements' => ['Length' => '', 'In Seam' => '']],
            ],
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        $shopId = $this->shopId($request);
        $clientId = $request->query('client_id');
        $search = $request->query('search');

        $query = StitchingSize::forShop($shopId)
            ->with('client:id,name,phone');

        if ($clientId) {
            $query->where('client_id', $clientId);
        }

        if ($search) {
            $query->whereHas('client', function ($q) use ($search) {
                $q->where('name', 'ilike', "%{$search}%")
                    ->orWhere('phone', 'ilike', "%{$search}%");
            });
        }

        $perPage = min(max((int) $request->query('per_page', 15), 5), 100);

        return response()->json($query->latest('measured_at')->paginate($perPage));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validateData($request);

        $record = StitchingSize::create([
            ...$data,
            'shop_id' => $this->shopId($request),
        ]);

        return response()->json($record->load('client:id,name,phone'), 201);
    }

    public function show(Request $request, StitchingSize $stitchingSize): JsonResponse
    {
        $this->authorize($request, $stitchingSize);

        return response()->json($stitchingSize->load('client'));
    }

    public function update(Request $request, StitchingSize $stitchingSize): JsonResponse
    {
        $this->authorize($request, $stitchingSize);

        $data = $this->validateData($request, true);
        $stitchingSize->update($data);

        return response()->json($stitchingSize->load('client:id,name,phone'));
    }

    public function destroy(Request $request, StitchingSize $stitchingSize): JsonResponse
    {
        $this->authorize($request, $stitchingSize);
        $stitchingSize->delete();

        return response()->json(['message' => 'Stitching size deleted.']);
    }

    private function validateData(Request $request, bool $partial = false): array
    {
        $data = $request->validate([
            'client_id' => [$partial ? 'sometimes' : 'required', 'exists:clients,id'],
            'label' => ['nullable', 'string', 'max:255'],
            'standard_size' => ['nullable', 'in:S,M,L,XL'],
            'sections' => [$partial ? 'sometimes' : 'required', 'array', 'min:1'],
            'sections.*.name' => ['nullable', 'string', 'max:100'],
            'sections.*.measurements' => ['required', 'array'],
            'notes' => ['nullable', 'string'],
            'measured_at' => [$partial ? 'sometimes' : 'required', 'date'],
        ]);

        if (isset($data['sections'])) {
            $sections = MeasurementHelper::normalizeSections($data['sections']);

            if (empty($sections)) {
                throw ValidationException::withMessages([
                    'sections' => ['At least one measurement field with a name and value is required.'],
                ]);
            }

            $data['sections'] = $sections;
        }

        return $data;
    }

    private function authorize(Request $request, StitchingSize $stitchingSize): void
    {
        abort_if($stitchingSize->shop_id !== $this->shopId($request), 404);
    }
}
