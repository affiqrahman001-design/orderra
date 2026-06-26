<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\MenuItem;
use App\Models\ModifierGroup;
use App\Models\ModifierOption;
use App\Services\Admin\AdminAuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

final class AdminModifierGroupController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = ModifierGroup::query()
            ->with(['menuItem.category', 'options'])
            ->withCount('options');

        if ($menuItemCode = strtoupper(trim($request->string('menu_item_code')->toString()))) {
            $menuItemId = MenuItem::query()->where('code', $menuItemCode)->value('id');
            $query->where('menu_item_id', $menuItemId ?: 0);
        }

        if ($request->has('is_active')) {
            $query->where('is_active', filter_var($request->input('is_active'), FILTER_VALIDATE_BOOLEAN));
        }

        if ($search = trim($request->string('q')->toString())) {
            $query->where(function ($builder) use ($search): void {
                $builder
                    ->where('code', 'like', '%'.$search.'%')
                    ->orWhere('name', 'like', '%'.$search.'%');
            });
        }

        $paginator = $query
            ->orderBy('sort_order')
            ->orderBy('id')
            ->paginate($this->perPage($request))
            ->appends($request->query());

        return response()->json([
            'data' => $paginator->getCollection()->map(
                fn (ModifierGroup $group) => $this->mapGroup($group)
            )->values(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ]);
    }

    public function show(string $groupId): JsonResponse
    {
        $group = $this->findGroup($groupId);

        return response()->json([
            'data' => $this->mapGroup($group),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $this->validatePayload($request);

        $menuItem = $this->resolveMenuItem($validated['menu_item_code']);

        $group = ModifierGroup::query()->create([
            'public_id' => (string) Str::uuid(),
            'menu_item_id' => $menuItem->id,
            'code' => strtoupper(trim($validated['code'])),
            'name' => $validated['name'],
            'helper_text' => $validated['helper_text'] ?? null,
            'selection_mode' => $validated['selection_mode'],
            'is_required' => $validated['is_required'] ?? false,
            'min_select' => $validated['min_select'] ?? 0,
            'max_select' => $validated['max_select'] ?? null,
            'sort_order' => $validated['sort_order'] ?? 0,
            'is_active' => $validated['is_active'] ?? true,
            'meta' => $validated['meta'] ?? null,
        ]);

        $this->upsertOptions($group, $validated['options'] ?? []);

        app(AdminAuditLogger::class)->logAdminAction(
            action: 'modifier_group.create',
            entityType: 'modifier_group',
            entityPublicId: $group->public_id,
            entitySecondaryKey: $group->code,
            summary: sprintf('Modifier group %s created.', $group->code),
            requestSnapshot: $request->all(),
            contextSnapshot: [
                'menu_item_code' => $menuItem->code,
                'selection_mode' => $group->selection_mode,
                'is_required' => (bool) $group->is_required,
                'options_count' => $group->options()->count(),
            ],
        );

        return response()->json([
            'message' => 'Modifier group created successfully.',
            'data' => $this->mapGroup($group->load(['menuItem.category', 'options'])),
        ], 201);
    }

    public function update(Request $request, string $groupId): JsonResponse
    {
        $group = $this->findGroup($groupId);
        $validated = $this->validatePayload($request, true, $group);

        if (array_key_exists('menu_item_code', $validated)) {
            $group->menu_item_id = $this->resolveMenuItem($validated['menu_item_code'])->id;
        }

        foreach ([
            'name',
            'helper_text',
            'selection_mode',
            'max_select',
            'meta',
        ] as $field) {
            if (array_key_exists($field, $validated)) {
                $group->{$field} = $validated[$field];
            }
        }

        if (array_key_exists('code', $validated)) {
            $group->code = strtoupper(trim($validated['code']));
        }

        if (array_key_exists('is_required', $validated)) {
            $group->is_required = $validated['is_required'];
        }

        if (array_key_exists('min_select', $validated)) {
            $group->min_select = $validated['min_select'];
        }

        if (array_key_exists('sort_order', $validated)) {
            $group->sort_order = $validated['sort_order'];
        }

        if (array_key_exists('is_active', $validated)) {
            $group->is_active = $validated['is_active'];
        }

        $group->save();

        if (array_key_exists('options', $validated)) {
            $this->upsertOptions($group, $validated['options']);
        }

        app(AdminAuditLogger::class)->logAdminAction(
            action: 'modifier_group.update',
            entityType: 'modifier_group',
            entityPublicId: $group->public_id,
            entitySecondaryKey: $group->code,
            summary: sprintf('Modifier group %s updated.', $group->code),
            requestSnapshot: $request->all(),
            contextSnapshot: [
                'menu_item_code' => $group->menuItem?->code,
                'selection_mode' => $group->selection_mode,
                'is_required' => (bool) $group->is_required,
                'options_count' => $group->options()->count(),
            ],
        );

        return response()->json([
            'message' => 'Modifier group updated successfully.',
            'data' => $this->mapGroup($group->refresh()->load(['menuItem.category', 'options'])),
        ]);
    }

    private function findGroup(string $groupId): ModifierGroup
    {
        return ModifierGroup::query()
            ->with(['menuItem.category', 'options'])
            ->where('public_id', $groupId)
            ->orWhere('code', strtoupper(trim($groupId)))
            ->firstOrFail();
    }

    private function resolveMenuItem(string $menuItemCode): MenuItem
    {
        return MenuItem::query()
            ->where('code', strtoupper(trim($menuItemCode)))
            ->firstOrFail();
    }

    private function validatePayload(Request $request, bool $isUpdate = false, ?ModifierGroup $group = null): array
    {
        $required = $isUpdate ? 'sometimes' : 'required';

        return Validator::make($request->all(), [
            'menu_item_code' => [$required, 'string', Rule::exists('menu_items', 'code')],
            'code' => [$required, 'string', 'max:80', Rule::unique('modifier_groups', 'code')->ignore($group?->id)],
            'name' => [$required, 'string', 'max:120'],
            'helper_text' => [$isUpdate ? 'sometimes' : 'nullable', 'nullable', 'string', 'max:160'],
            'selection_mode' => [$required, Rule::in((array) config('reference_settings.catalog.selection_modes', []))],
            'is_required' => [$isUpdate ? 'sometimes' : 'nullable', 'boolean'],
            'min_select' => [$isUpdate ? 'sometimes' : 'nullable', 'integer', 'min:0'],
            'max_select' => [$isUpdate ? 'sometimes' : 'nullable', 'nullable', 'integer', 'min:1'],
            'sort_order' => [$isUpdate ? 'sometimes' : 'nullable', 'integer', 'min:0'],
            'is_active' => [$isUpdate ? 'sometimes' : 'nullable', 'boolean'],
            'meta' => [$isUpdate ? 'sometimes' : 'nullable', 'nullable', 'array'],
            'options' => [$isUpdate ? 'sometimes' : 'nullable', 'array'],
            'options.*.code' => ['required_with:options', 'string', 'max:80'],
            'options.*.label' => ['required_with:options', 'string', 'max:140'],
            'options.*.price_delta' => ['nullable', 'numeric', 'min:0'],
            'options.*.is_default' => ['nullable', 'boolean'],
            'options.*.sort_order' => ['nullable', 'integer', 'min:0'],
            'options.*.is_active' => ['nullable', 'boolean'],
            'options.*.meta' => ['nullable', 'array'],
        ])->validate();
    }

    private function upsertOptions(ModifierGroup $group, array $options): void
    {
        foreach ($options as $option) {
            ModifierOption::query()->updateOrCreate(
                [
                    'modifier_group_id' => $group->id,
                    'code' => strtoupper(trim((string) $option['code'])),
                ],
                [
                    'public_id' => (string) Str::uuid(),
                    'label' => $option['label'],
                    'price_delta_amount' => $this->toCents($option['price_delta'] ?? 0),
                    'is_default' => $option['is_default'] ?? false,
                    'sort_order' => $option['sort_order'] ?? 0,
                    'is_active' => $option['is_active'] ?? true,
                    'meta' => $option['meta'] ?? null,
                ]
            );
        }
    }

    private function mapGroup(ModifierGroup $group): array
    {
        return [
            'id' => $group->public_id,
            'menu_item_code' => $group->menuItem?->code,
            'menu_item_name' => $group->menuItem?->name,
            'category_code' => $group->menuItem?->category?->code,
            'code' => $group->code,
            'name' => $group->name,
            'helper_text' => $group->helper_text,
            'selection_mode' => $group->selection_mode,
            'is_required' => (bool) $group->is_required,
            'min_select' => $group->min_select,
            'max_select' => $group->max_select,
            'sort_order' => $group->sort_order,
            'is_active' => (bool) $group->is_active,
            'options_count' => $group->options_count ?? $group->options()->count(),
            'options' => $group->options->map(fn (ModifierOption $option) => [
                'id' => $option->public_id,
                'code' => $option->code,
                'label' => $option->label,
                'price_delta' => round(((int) $option->price_delta_amount) / 100, 2),
                'is_default' => (bool) $option->is_default,
                'sort_order' => $option->sort_order,
                'is_active' => (bool) $option->is_active,
                'meta' => $option->meta,
            ])->values(),
            'meta' => $group->meta,
            'created_at' => optional($group->created_at)?->toIso8601String(),
            'updated_at' => optional($group->updated_at)?->toIso8601String(),
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
