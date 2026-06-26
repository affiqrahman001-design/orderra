<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\MenuCategory;
use App\Services\Admin\AdminAuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

final class AdminMenuCategoryController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = MenuCategory::query()->withCount('items');

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
            ->orderBy('sort_order')
            ->orderBy('id')
            ->paginate($this->perPage($request))
            ->appends($request->query());

        return response()->json([
            'data' => $paginator->getCollection()->map(
                fn (MenuCategory $category) => $this->mapCategory($category)
            )->values(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ]);
    }

    public function show(string $categoryId): JsonResponse
    {
        $category = $this->findCategory($categoryId);

        return response()->json([
            'data' => $this->mapCategory($category),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $this->validatePayload($request);

        $category = MenuCategory::query()->create([
            'public_id' => (string) Str::uuid(),
            'code' => strtoupper(trim($validated['code'])),
            'name' => $validated['name'],
            'slug' => $validated['slug'],
            'description' => $validated['description'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
            'sort_order' => $validated['sort_order'] ?? 0,
            'meta' => $validated['meta'] ?? null,
        ]);

        app(AdminAuditLogger::class)->logAdminAction(
            action: 'menu_category.create',
            entityType: 'menu_category',
            entityPublicId: $category->public_id,
            entitySecondaryKey: $category->code,
            summary: sprintf('Menu category %s created.', $category->code),
            requestSnapshot: $request->all(),
            contextSnapshot: [
                'name' => $category->name,
                'slug' => $category->slug,
                'is_active' => (bool) $category->is_active,
            ],
        );

        return response()->json([
            'message' => 'Menu category created successfully.',
            'data' => $this->mapCategory($category),
        ], 201);
    }

    public function update(Request $request, string $categoryId): JsonResponse
    {
        $category = $this->findCategory($categoryId);
        $validated = $this->validatePayload($request, true, $category);

        foreach (['name', 'slug', 'description', 'meta'] as $field) {
            if (array_key_exists($field, $validated)) {
                $category->{$field} = $validated[$field];
            }
        }

        if (array_key_exists('code', $validated)) {
            $category->code = strtoupper(trim($validated['code']));
        }

        if (array_key_exists('is_active', $validated)) {
            $category->is_active = $validated['is_active'];
        }

        if (array_key_exists('sort_order', $validated)) {
            $category->sort_order = $validated['sort_order'];
        }

        $category->save();

        app(AdminAuditLogger::class)->logAdminAction(
            action: 'menu_category.update',
            entityType: 'menu_category',
            entityPublicId: $category->public_id,
            entitySecondaryKey: $category->code,
            summary: sprintf('Menu category %s updated.', $category->code),
            requestSnapshot: $request->all(),
            contextSnapshot: [
                'name' => $category->name,
                'slug' => $category->slug,
                'is_active' => (bool) $category->is_active,
            ],
        );

        return response()->json([
            'message' => 'Menu category updated successfully.',
            'data' => $this->mapCategory($category->refresh()),
        ]);
    }

    private function findCategory(string $categoryId): MenuCategory
    {
        return MenuCategory::query()
            ->where('public_id', $categoryId)
            ->orWhere('code', strtoupper(trim($categoryId)))
            ->firstOrFail();
    }

    private function validatePayload(Request $request, bool $isUpdate = false, ?MenuCategory $category = null): array
    {
        $required = $isUpdate ? 'sometimes' : 'required';

        return Validator::make($request->all(), [
            'code' => [$required, 'string', 'max:40', Rule::unique('menu_categories', 'code')->ignore($category?->id)],
            'name' => [$required, 'string', 'max:120'],
            'slug' => [$required, 'string', 'max:120', Rule::unique('menu_categories', 'slug')->ignore($category?->id)],
            'description' => [$isUpdate ? 'sometimes' : 'nullable', 'nullable', 'string'],
            'is_active' => [$isUpdate ? 'sometimes' : 'nullable', 'boolean'],
            'sort_order' => [$isUpdate ? 'sometimes' : 'nullable', 'integer', 'min:0'],
            'meta' => [$isUpdate ? 'sometimes' : 'nullable', 'nullable', 'array'],
        ])->validate();
    }

    private function mapCategory(MenuCategory $category): array
    {
        return [
            'id' => $category->public_id,
            'code' => $category->code,
            'name' => $category->name,
            'slug' => $category->slug,
            'description' => $category->description,
            'is_active' => (bool) $category->is_active,
            'sort_order' => $category->sort_order,
            'items_count' => $category->items_count ?? $category->items()->count(),
            'meta' => $category->meta,
            'created_at' => optional($category->created_at)?->toIso8601String(),
            'updated_at' => optional($category->updated_at)?->toIso8601String(),
        ];
    }

    private function perPage(Request $request): int
    {
        $default = (int) config('reference_settings.pagination.default_per_page', 15);
        $max = (int) config('reference_settings.pagination.max_per_page', 50);

        return min(max(1, (int) $request->integer('per_page', $default)), $max);
    }
}
