<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Customer;

use App\Http\Controllers\Controller;
use App\Models\MenuCategory;
use App\Models\MenuItem;
use Illuminate\Http\JsonResponse;

final class CatalogOverviewController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $categories = MenuCategory::query()
            ->withCount(['items' => fn ($query) => $query->where('is_active', true)])
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $items = MenuItem::query()
            ->with(['category', 'modifierGroups.options'])
            ->where('is_active', true)
            ->orderByDesc('is_featured')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return response()->json([
            'data' => [
                'categories' => $categories->map(fn (MenuCategory $category) => [
                    'id' => $category->public_id,
                    'code' => $category->code,
                    'name' => $category->name,
                    'slug' => $category->slug,
                    'description' => $category->description,
                    'is_active' => (bool) $category->is_active,
                    'sort_order' => $category->sort_order,
                    'item_count' => $category->items_count,
                ])->values(),

                'items' => $items->map(fn (MenuItem $item) => $this->mapItem($item))->values(),
            ],
            'meta' => [
                'demo' => true,
                'total_categories' => $categories->count(),
                'total_items' => $items->count(),
            ],
        ]);
    }

    private function mapItem(MenuItem $item): array
    {
        return [
            'id' => $item->public_id,
            'code' => $item->code,
            'name' => $item->name,
            'short_name' => $item->short_name,
            'slug' => $item->slug,
            'description' => $item->description,
            'category_slug' => $item->category?->slug,
            'price' => round(((int) $item->base_price_amount) / 100, 2),
            'currency' => $item->currency,
            'image_url' => $item->image_url,
            'is_available' => (bool) $item->is_active,
            'featured' => (bool) $item->is_featured,
            'badge_label' => $item->badge_label,
            'prep_note' => $item->prep_note,
            'flow' => $item->product_flow,
            'modifier_groups' => $item->modifierGroups->where('is_active', true)->values()->map(function ($group): array {
                return [
                    'id' => $group->public_id,
                    'code' => $group->code,
                    'label' => $group->name,
                    'helper_text' => $group->helper_text,
                    'selection_mode' => $group->selection_mode,
                    'required' => (bool) $group->is_required,
                    'min_select' => $group->min_select,
                    'max_select' => $group->max_select,
                    'options' => $group->options->where('is_active', true)->values()->map(fn ($option) => [
                        'id' => $option->public_id,
                        'code' => $option->code,
                        'label' => $option->label,
                        'price_delta' => round(((int) $option->price_delta_amount) / 100, 2),
                        'is_default' => (bool) $option->is_default,
                    ])->values(),
                ];
            })->values(),
        ];
    }
}
