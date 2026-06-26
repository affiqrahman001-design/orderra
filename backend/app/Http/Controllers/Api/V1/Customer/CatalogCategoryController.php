<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Customer;

use App\Http\Controllers\Controller;
use App\Models\MenuCategory;
use Illuminate\Http\JsonResponse;

final class CatalogCategoryController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $categories = MenuCategory::query()
            ->withCount(['items' => fn ($query) => $query->where('is_active', true)])
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return response()->json([
            'data' => $categories->map(fn (MenuCategory $category) => [
                'id' => $category->public_id,
                'code' => $category->code,
                'name' => $category->name,
                'slug' => $category->slug,
                'description' => $category->description,
                'is_active' => (bool) $category->is_active,
                'sort_order' => $category->sort_order,
                'item_count' => $category->items_count,
            ])->values(),
            'meta' => [
                'demo' => true,
            ],
        ]);
    }
}
