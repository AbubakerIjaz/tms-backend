<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\AppliesListingFilters;
use App\Models\Design;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class DesignController extends ShopController
{
    use AppliesListingFilters;

    public function index(Request $request): JsonResponse
    {
        $shopId = $this->shopId($request);
        $search = $this->searchTerm($request);
        $garmentTypeId = $request->query('garment_type_id');
        $perPage = $this->listingPerPage($request);

        $query = Design::forShop($shopId)
            ->with('garmentType:id,name')
            ->withCount(Design::activeLinkCountQueries());

        $this->applyColumnSearch($query, $search, ['name', 'description']);
        if ($garmentTypeId) {
            $query->where('garment_type_id', $garmentTypeId);
        }

        $this->applyDateRangeFilter($query, $request, 'created_at');

        return response()->json($query->latest()->paginate($perPage)->through(
            fn (Design $design) => $this->formatDesign($design)
        ));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'garment_type_id' => ['nullable', 'exists:garment_types,id'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'base_price' => ['nullable', 'numeric', 'min:0'],
            'image' => ['nullable', 'image', 'max:5120'],
            'is_active' => ['boolean'],
        ]);

        if ($request->hasFile('image')) {
            $data['image_path'] = $request->file('image')->store('designs', 'public');
        }
        unset($data['image']);

        $design = Design::create([
            ...$data,
            'shop_id' => $this->shopId($request),
        ]);

        return response()->json($this->formatDesign($design->load('garmentType:id,name')->loadCount(Design::activeLinkCountQueries())), 201);
    }

    public function show(Request $request, Design $design): JsonResponse
    {
        $this->authorize($request, $design);

        return response()->json($this->formatDesign($design->load('garmentType:id,name')->loadCount(Design::activeLinkCountQueries())));
    }

    public function update(Request $request, Design $design): JsonResponse
    {
        $this->authorize($request, $design);
        $this->ensureDesignEditable($design);

        $data = $request->validate([
            'garment_type_id' => ['nullable', 'exists:garment_types,id'],
            'name' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'base_price' => ['nullable', 'numeric', 'min:0'],
            'image' => ['nullable', 'image', 'max:5120'],
            'is_active' => ['boolean'],
        ]);

        if ($request->hasFile('image')) {
            if ($design->image_path) {
                Storage::disk('public')->delete($design->image_path);
            }
            $data['image_path'] = $request->file('image')->store('designs', 'public');
        }
        unset($data['image']);

        $design->update($data);

        return response()->json($this->formatDesign($design->load('garmentType:id,name')->loadCount(Design::activeLinkCountQueries())));
    }

    public function destroy(Request $request, Design $design): JsonResponse
    {
        $this->authorize($request, $design);
        $this->ensureDesignEditable($design);

        if ($design->image_path) {
            Storage::disk('public')->delete($design->image_path);
        }
        $design->delete();

        return response()->json(['message' => 'Design deleted.']);
    }

    private function authorize(Request $request, Design $design): void
    {
        abort_if($design->shop_id !== $this->shopId($request), 404);
    }

    private function ensureDesignEditable(Design $design): void
    {
        if ($design->isLinkedToOrders()) {
            throw ValidationException::withMessages([
                'design' => ['This design is linked to one or more orders and cannot be edited or deleted.'],
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function formatDesign(Design $design): array
    {
        $ordersCount = (int) ($design->active_orders_count ?? 0);
        $orderItemsCount = (int) ($design->active_order_items_count ?? 0);
        $linked = $ordersCount + $orderItemsCount;

        return [
            ...$design->toArray(),
            'orders_count' => $linked,
            'is_locked' => $linked > 0,
        ];
    }
}
