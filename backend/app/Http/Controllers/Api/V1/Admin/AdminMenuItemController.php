<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\MenuCategory;
use App\Models\MenuItem;
use App\Services\Admin\AdminAuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

final class AdminMenuItemController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = MenuItem::query()->with(['category', 'branch', 'modifierGroups.options']);

        if ($categoryCode = strtoupper(trim($request->string('category_code')->toString()))) {
            $categoryId = MenuCategory::query()->where('code', $categoryCode)->value('id');
            $query->where('menu_category_id', $categoryId ?: 0);
        }

        if ($branchCode = strtoupper(trim($request->string('branch_code')->toString()))) {
            $branchId = Branch::query()->where('code', $branchCode)->value('id');
            $query->where('branch_id', $branchId ?: 0);
        }

        if ($request->has('is_active')) {
            $query->where('is_active', filter_var($request->input('is_active'), FILTER_VALIDATE_BOOLEAN));
        }

        if ($search = trim($request->string('q')->toString())) {
            $query->where(function ($builder) use ($search): void {
                $builder
                    ->where('code', 'like', '%'.$search.'%')
                    ->orWhere('name', 'like', '%'.$search.'%')
                    ->orWhere('slug', 'like', '%'.$search.'%');
            });
        }

        $paginator = $query
            ->orderByDesc('is_featured')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->paginate($this->perPage($request))
            ->appends($request->query());

        return response()->json([
            'data' => $paginator->getCollection()->map(
                fn (MenuItem $item) => $this->mapItem($item)
            )->values(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ]);
    }

    public function show(string $itemId): JsonResponse
    {
        $item = $this->findItem($itemId);

        return response()->json([
            'data' => $this->mapItem($item),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $this->validatePayload($request);

        $category = MenuCategory::query()
            ->where('code', strtoupper(trim($validated['category_code'])))
            ->firstOrFail();

        $branch = null;

        if (! empty($validated['branch_code'])) {
            $branch = Branch::query()
                ->where('code', strtoupper(trim($validated['branch_code'])))
                ->firstOrFail();
        }

        $item = MenuItem::query()->create([
            'public_id' => (string) Str::uuid(),
            'branch_id' => $branch?->id,
            'menu_category_id' => $category->id,
            'code' => strtoupper(trim($validated['code'])),
            'name' => $validated['name'],
            'short_name' => $validated['short_name'] ?? null,
            'slug' => $validated['slug'],
            'description' => $validated['description'] ?? null,
            'base_price_amount' => $this->toCents($validated['base_price']),
            'currency' => strtoupper($validated['currency'] ?? 'USD'),
            'image_url' => $validated['image_url'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
            'is_featured' => $validated['is_featured'] ?? false,
            'badge_label' => $validated['badge_label'] ?? null,
            'prep_note' => $validated['prep_note'] ?? null,
            'product_flow' => $validated['product_flow'] ?? 'full',
            'sort_order' => $validated['sort_order'] ?? 0,
            'meta' => $validated['meta'] ?? null,
        ]);

        app(AdminAuditLogger::class)->logAdminAction(
            action: 'menu_item.create',
            entityType: 'menu_item',
            entityPublicId: $item->public_id,
            entitySecondaryKey: $item->code,
            summary: sprintf('Menu item %s created.', $item->code),
            requestSnapshot: $request->all(),
            contextSnapshot: [
                'category_code' => $category->code,
                'branch_code' => $branch?->code,
                'is_active' => (bool) $item->is_active,
                'is_featured' => (bool) $item->is_featured,
            ],
        );

        return response()->json([
            'message' => 'Menu item created successfully.',
            'data' => $this->mapItem($item->load(['category', 'branch', 'modifierGroups.options'])),
        ], 201);
    }

    public function update(Request $request, string $itemId): JsonResponse
    {
        $item = $this->findItem($itemId);
        $validated = $this->validatePayload($request, true, $item);

        if (array_key_exists('category_code', $validated)) {
            $item->menu_category_id = MenuCategory::query()
                ->where('code', strtoupper(trim($validated['category_code'])))
                ->value('id');
        }

        if (array_key_exists('branch_code', $validated)) {
            $item->branch_id = empty($validated['branch_code'])
              ? null
              : Branch::query()->where('code', strtoupper(trim($validated['branch_code'])))->value('id');
        }

        foreach ([
            'name',
            'short_name',
            'slug',
            'description',
            'image_url',
            'badge_label',
            'prep_note',
            'product_flow',
            'meta',
        ] as $field) {
            if (array_key_exists($field, $validated)) {
                $item->{$field} = $validated[$field];
            }
        }

        if (array_key_exists('code', $validated)) {
            $item->code = strtoupper(trim($validated['code']));
        }

        if (array_key_exists('base_price', $validated)) {
            $item->base_price_amount = $this->toCents($validated['base_price']);
        }

        if (array_key_exists('currency', $validated)) {
            $item->currency = strtoupper($validated['currency']);
        }

        if (array_key_exists('is_active', $validated)) {
            $item->is_active = $validated['is_active'];
        }

        if (array_key_exists('is_featured', $validated)) {
            $item->is_featured = $validated['is_featured'];
        }

        if (array_key_exists('sort_order', $validated)) {
            $item->sort_order = $validated['sort_order'];
        }

        $item->save();

        app(AdminAuditLogger::class)->logAdminAction(
            action: 'menu_item.update',
            entityType: 'menu_item',
            entityPublicId: $item->public_id,
            entitySecondaryKey: $item->code,
            summary: sprintf('Menu item %s updated.', $item->code),
            requestSnapshot: $request->all(),
            contextSnapshot: [
                'category_code' => $item->category?->code,
                'branch_code' => $item->branch?->code,
                'is_active' => (bool) $item->is_active,
                'is_featured' => (bool) $item->is_featured,
            ],
        );

        return response()->json([
            'message' => 'Menu item updated successfully.',
            'data' => $this->mapItem($item->refresh()->load(['category', 'branch', 'modifierGroups.options'])),
        ]);
    }

    private function findItem(string $itemId): MenuItem
    {
        return MenuItem::query()
            ->with(['category', 'branch', 'modifierGroups.options'])
            ->where('public_id', $itemId)
            ->orWhere('code', strtoupper(trim($itemId)))
            ->firstOrFail();
    }

    private function validatePayload(Request $request, bool $isUpdate = false, ?MenuItem $item = null): array
    {
        $required = $isUpdate ? 'sometimes' : 'required';

        return Validator::make($request->all(), [
            'branch_code' => [$isUpdate ? 'sometimes' : 'nullable', 'nullable', 'string', 'max:40'],
            'category_code' => [$required, 'string', Rule::exists('menu_categories', 'code')],
            'code' => [$required, 'string', 'max:60', Rule::unique('menu_items', 'code')->ignore($item?->id)],
            'name' => [$required, 'string', 'max:140'],
            'short_name' => [$isUpdate ? 'sometimes' : 'nullable', 'nullable', 'string', 'max:80'],
            'slug' => [$required, 'string', 'max:140', Rule::unique('menu_items', 'slug')->ignore($item?->id)],
            'description' => [$isUpdate ? 'sometimes' : 'nullable', 'nullable', 'string'],
            'base_price' => [$required, 'numeric', 'min:0'],
            'currency' => [$isUpdate ? 'sometimes' : 'nullable', 'nullable', 'string', 'size:3'],
            'image_url' => [$isUpdate ? 'sometimes' : 'nullable', 'nullable', 'string', 'max:255'],
            'is_active' => [$isUpdate ? 'sometimes' : 'nullable', 'boolean'],
            'is_featured' => [$isUpdate ? 'sometimes' : 'nullable', 'boolean'],
            'badge_label' => [$isUpdate ? 'sometimes' : 'nullable', 'nullable', 'string', 'max:60'],
            'prep_note' => [$isUpdate ? 'sometimes' : 'nullable', 'nullable', 'string', 'max:160'],
            'product_flow' => [$isUpdate ? 'sometimes' : 'nullable', Rule::in((array) config('reference_settings.catalog.product_flows', []))],
            'sort_order' => [$isUpdate ? 'sometimes' : 'nullable', 'integer', 'min:0'],
            'meta' => [$isUpdate ? 'sometimes' : 'nullable', 'nullable', 'array'],
        ])->validate();
    }

    private function mapItem(MenuItem $item): array
    {
        return [
            'id' => $item->public_id,
            'branch_code' => $item->branch?->code,
            'category_code' => $item->category?->code,
            'category_name' => $item->category?->name,
            'code' => $item->code,
            'name' => $item->name,
            'short_name' => $item->short_name,
            'slug' => $item->slug,
            'description' => $item->description,
            'base_price' => round(((int) $item->base_price_amount) / 100, 2),
            'currency' => $item->currency,
            'image_url' => $item->image_url,
            'is_active' => (bool) $item->is_active,
            'is_featured' => (bool) $item->is_featured,
            'badge_label' => $item->badge_label,
            'prep_note' => $item->prep_note,
            'product_flow' => $item->product_flow,
            'sort_order' => $item->sort_order,
            'modifier_groups_count' => $item->modifierGroups->count(),
            'meta' => $item->meta,
            'created_at' => optional($item->created_at)?->toIso8601String(),
            'updated_at' => optional($item->updated_at)?->toIso8601String(),
        ];
    }

    private function toCents(mixed $value): int
    {
        return (int) round(((float) $value) * 100);
    }

    private function perPage(Request $request): int
    {
        $default = (int) config('reference_settings.pagination.default_per_page', 15);
        $max = (int) config('reference_settings.pagination.max_per_page', 50);

        return min(max(1, (int) $request->integer('per_page', $default)), $max);
    }
}
