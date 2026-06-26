<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Promo;
use App\Services\Admin\AdminAuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

final class AdminPromoController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Promo::query();

        if ($request->has('is_active')) {
            $query->where('is_active', filter_var($request->input('is_active'), FILTER_VALIDATE_BOOLEAN));
        }

        if ($search = trim($request->string('q')->toString())) {
            $query->where(function ($builder) use ($search): void {
                $builder
                    ->where('code', 'like', '%'.$search.'%')
                    ->orWhere('title', 'like', '%'.$search.'%');
            });
        }

        $paginator = $query
            ->latest('id')
            ->paginate($this->perPage($request))
            ->appends($request->query());

        return response()->json([
            'data' => $paginator->getCollection()->map(
                fn (Promo $promo) => $this->mapPromo($promo)
            )->values(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ]);
    }

    public function show(string $promoId): JsonResponse
    {
        $promo = $this->findPromo($promoId);

        return response()->json([
            'data' => $this->mapPromo($promo),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $this->validatePayload($request);

        $promo = Promo::query()->create([
            'public_id' => (string) Str::uuid(),
            'code' => strtoupper(trim($validated['code'])),
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'discount_type' => $validated['discount_type'],
            'value_bps' => $validated['discount_type'] === 'percentage'
              ? $this->toBps($validated['percentage_rate'])
              : null,
            'fixed_amount' => $validated['discount_type'] === 'fixed'
              ? $this->toCents($validated['fixed_amount'])
              : null,
            'minimum_subtotal_amount' => isset($validated['minimum_subtotal'])
              ? $this->toCents($validated['minimum_subtotal'])
              : null,
            'badge_label' => $validated['badge_label'] ?? null,
            'starts_at' => $validated['starts_at'] ?? null,
            'ends_at' => $validated['ends_at'] ?? null,
            'usage_limit' => $validated['usage_limit'] ?? null,
            'per_user_limit' => $validated['per_user_limit'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
            'meta' => $validated['meta'] ?? null,
        ]);

        app(AdminAuditLogger::class)->logAdminAction(
            action: 'promo.create',
            entityType: 'promo',
            entityPublicId: $promo->public_id,
            entitySecondaryKey: $promo->code,
            summary: sprintf('Promo %s created.', $promo->code),
            requestSnapshot: $request->all(),
            contextSnapshot: [
                'discount_type' => $promo->discount_type,
                'is_active' => (bool) $promo->is_active,
            ],
        );

        return response()->json([
            'message' => 'Promo created successfully.',
            'data' => $this->mapPromo($promo),
        ], 201);
    }

    public function update(Request $request, string $promoId): JsonResponse
    {
        $promo = $this->findPromo($promoId);
        $validated = $this->validatePayload($request, true, $promo);

        foreach ([
            'title',
            'description',
            'discount_type',
            'badge_label',
            'starts_at',
            'ends_at',
            'usage_limit',
            'per_user_limit',
            'meta',
        ] as $field) {
            if (array_key_exists($field, $validated)) {
                $promo->{$field} = $validated[$field];
            }
        }

        if (array_key_exists('code', $validated)) {
            $promo->code = strtoupper(trim($validated['code']));
        }

        if (array_key_exists('minimum_subtotal', $validated)) {
            $promo->minimum_subtotal_amount = $validated['minimum_subtotal'] !== null
              ? $this->toCents($validated['minimum_subtotal'])
              : null;
        }

        if (array_key_exists('is_active', $validated)) {
            $promo->is_active = $validated['is_active'];
        }

        if (($validated['discount_type'] ?? $promo->discount_type) === 'percentage') {
            if (array_key_exists('percentage_rate', $validated)) {
                $promo->value_bps = $this->toBps($validated['percentage_rate']);
            }
            if (array_key_exists('discount_type', $validated)) {
                $promo->fixed_amount = null;
            }
        }

        if (($validated['discount_type'] ?? $promo->discount_type) === 'fixed') {
            if (array_key_exists('fixed_amount', $validated)) {
                $promo->fixed_amount = $this->toCents($validated['fixed_amount']);
            }
            if (array_key_exists('discount_type', $validated)) {
                $promo->value_bps = null;
            }
        }

        $promo->save();

        app(AdminAuditLogger::class)->logAdminAction(
            action: 'promo.update',
            entityType: 'promo',
            entityPublicId: $promo->public_id,
            entitySecondaryKey: $promo->code,
            summary: sprintf('Promo %s updated.', $promo->code),
            requestSnapshot: $request->all(),
            contextSnapshot: [
                'discount_type' => $promo->discount_type,
                'is_active' => (bool) $promo->is_active,
            ],
        );

        return response()->json([
            'message' => 'Promo updated successfully.',
            'data' => $this->mapPromo($promo->refresh()),
        ]);
    }

    private function findPromo(string $promoId): Promo
    {
        return Promo::query()
            ->where('public_id', $promoId)
            ->orWhere('code', strtoupper(trim($promoId)))
            ->firstOrFail();
    }

    private function validatePayload(Request $request, bool $isUpdate = false, ?Promo $promo = null): array
    {
        $required = $isUpdate ? 'sometimes' : 'required';

        $data = Validator::make($request->all(), [
            'code' => [$required, 'string', 'max:50', Rule::unique('promos', 'code')->ignore($promo?->id)],
            'title' => [$required, 'string', 'max:160'],
            'description' => [$isUpdate ? 'sometimes' : 'nullable', 'nullable', 'string'],
            'discount_type' => [$required, Rule::in((array) config('reference_settings.promos.discount_types', []))],
            'percentage_rate' => [$isUpdate ? 'sometimes' : 'nullable', 'nullable', 'numeric', 'min:0', 'max:100'],
            'fixed_amount' => [$isUpdate ? 'sometimes' : 'nullable', 'nullable', 'numeric', 'min:0'],
            'minimum_subtotal' => [$isUpdate ? 'sometimes' : 'nullable', 'nullable', 'numeric', 'min:0'],
            'badge_label' => [$isUpdate ? 'sometimes' : 'nullable', 'nullable', 'string', 'max:60'],
            'starts_at' => [$isUpdate ? 'sometimes' : 'nullable', 'nullable', 'date'],
            'ends_at' => [$isUpdate ? 'sometimes' : 'nullable', 'nullable', 'date', 'after:starts_at'],
            'usage_limit' => [$isUpdate ? 'sometimes' : 'nullable', 'nullable', 'integer', 'min:1'],
            'per_user_limit' => [$isUpdate ? 'sometimes' : 'nullable', 'nullable', 'integer', 'min:1'],
            'is_active' => [$isUpdate ? 'sometimes' : 'nullable', 'boolean'],
            'meta' => [$isUpdate ? 'sometimes' : 'nullable', 'nullable', 'array'],
        ])->validate();

        $discountType = $data['discount_type'] ?? $promo?->discount_type;

        if ($discountType === 'percentage' && ! $isUpdate && ! array_key_exists('percentage_rate', $data)) {
            abort(422, 'percentage_rate is required for percentage promo.');
        }

        if ($discountType === 'fixed' && ! $isUpdate && ! array_key_exists('fixed_amount', $data)) {
            abort(422, 'fixed_amount is required for fixed promo.');
        }

        return $data;
    }

    private function mapPromo(Promo $promo): array
    {
        return [
            'id' => $promo->public_id,
            'code' => $promo->code,
            'title' => $promo->title,
            'description' => $promo->description,
            'discount_type' => $promo->discount_type,
            'percentage_rate' => $promo->value_bps !== null ? round($promo->value_bps / 100, 2) : null,
            'fixed_amount' => $promo->fixed_amount !== null ? round($promo->fixed_amount / 100, 2) : null,
            'minimum_subtotal' => $promo->minimum_subtotal_amount !== null ? round($promo->minimum_subtotal_amount / 100, 2) : null,
            'badge_label' => $promo->badge_label,
            'starts_at' => optional($promo->starts_at)?->toIso8601String(),
            'ends_at' => optional($promo->ends_at)?->toIso8601String(),
            'usage_limit' => $promo->usage_limit,
            'per_user_limit' => $promo->per_user_limit,
            'is_active' => (bool) $promo->is_active,
            'meta' => $promo->meta,
            'created_at' => optional($promo->created_at)?->toIso8601String(),
            'updated_at' => optional($promo->updated_at)?->toIso8601String(),
        ];
    }

    private function toCents(mixed $value): int
    {
        return (int) round(((float) $value) * 100);
    }

    private function toBps(mixed $value): int
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
