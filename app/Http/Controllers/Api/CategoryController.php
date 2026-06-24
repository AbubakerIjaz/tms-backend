<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\AppliesListingFilters;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CategoryController extends ShopController
{
    use AppliesListingFilters;

    public function index(Request $request): JsonResponse
    {
        $query = Category::forShop($this->shopId($request))
            ->withCount('galleryItems')
            ->orderBy('name');

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
            'is_active' => ['boolean'],
        ]);

        $slug = Str::slug($data['name']);
        $baseSlug = $slug;
        $counter = 1;
        $shopId = $this->shopId($request);
        while (Category::forShop($shopId)->where('slug', $slug)->exists()) {
            $slug = $baseSlug.'-'.$counter++;
        }

        $category = Category::create([
            ...$data,
            'shop_id' => $shopId,
            'slug' => $slug,
        ]);

        return response()->json($category, 201);
    }

    public function update(Request $request, Category $category): JsonResponse
    {
        $this->authorize($request, $category);

        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ]);

        if (isset($data['name'])) {
            $slug = Str::slug($data['name']);
            $baseSlug = $slug;
            $counter = 1;
            $shopId = $this->shopId($request);
            while (Category::forShop($shopId)->where('slug', $slug)->where('id', '!=', $category->id)->exists()) {
                $slug = $baseSlug.'-'.$counter++;
            }
            $data['slug'] = $slug;
        }

        $category->update($data);

        return response()->json($category);
    }

    public function destroy(Request $request, Category $category): JsonResponse
    {
        $this->authorize($request, $category);
        $category->delete();

        return response()->json(['message' => 'Category deleted.']);
    }

    private function authorize(Request $request, Category $category): void
    {
        abort_if($category->shop_id !== $this->shopId($request), 404);
    }
}
