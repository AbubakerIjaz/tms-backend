<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\AppliesListingFilters;
use App\Models\GalleryItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class GalleryController extends ShopController
{
    use AppliesListingFilters;

    public function index(Request $request): JsonResponse
    {
        $shopId = $this->shopId($request);
        $categoryId = $request->query('category_id');
        $search = $this->searchTerm($request);
        $perPage = $this->listingPerPage($request);

        $query = GalleryItem::forShop($shopId)->with('category:id,name,slug');

        if ($categoryId) {
            $query->where('category_id', $categoryId);
        }

        $this->applyColumnSearch($query, $search, ['title', 'description']);

        $this->applyDateRangeFilter($query, $request, 'created_at');

        return response()->json($query->latest()->paginate($perPage));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'category_id' => ['nullable', 'exists:categories,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'image' => ['required', 'image', 'max:5120'],
            'is_active' => ['boolean'],
        ]);

        $data['image_path'] = $request->file('image')->store('gallery', 'public');
        unset($data['image']);

        $item = GalleryItem::create([
            ...$data,
            'shop_id' => $this->shopId($request),
        ]);

        return response()->json($item->load('category:id,name,slug'), 201);
    }

    public function update(Request $request, GalleryItem $galleryItem): JsonResponse
    {
        $this->authorize($request, $galleryItem);

        $data = $request->validate([
            'category_id' => ['nullable', 'exists:categories,id'],
            'title' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'image' => ['nullable', 'image', 'max:5120'],
            'is_active' => ['boolean'],
        ]);

        if ($request->hasFile('image')) {
            if ($galleryItem->image_path) {
                Storage::disk('public')->delete($galleryItem->image_path);
            }
            $data['image_path'] = $request->file('image')->store('gallery', 'public');
        }
        unset($data['image']);

        $galleryItem->update($data);

        return response()->json($galleryItem->load('category:id,name,slug'));
    }

    public function destroy(Request $request, GalleryItem $galleryItem): JsonResponse
    {
        $this->authorize($request, $galleryItem);

        if ($galleryItem->image_path) {
            Storage::disk('public')->delete($galleryItem->image_path);
        }
        $galleryItem->delete();

        return response()->json(['message' => 'Gallery item deleted.']);
    }

    private function authorize(Request $request, GalleryItem $galleryItem): void
    {
        abort_if($galleryItem->shop_id !== $this->shopId($request), 404);
    }
}
