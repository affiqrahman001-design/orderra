<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\TaxRule;
use App\Services\Admin\AdminAuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

final class AdminTaxRuleController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = TaxRule::query();

        if ($branchCode = strtoupper(trim($request->string('branch_code')->toString()))) {
            $branch = Branch::query()->where('code', $branchCode)->first();
            $query->where('branch_id', $branch?->id ?? 0);
        }

        if ($fulfillmentType = $request->string('fulfillment_type')->toString()) {
            $query->where('fulfillment_type', $fulfillmentType);
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $this->toBool($request->input('is_active')));
        }

        if ($search = trim($request->string('q')->toString())) {
            $query->where(function ($builder) use ($search): void {
                $builder
                    ->where('name', 'like', '%'.$search.'%')
                    ->orWhere('country_code', 'like', '%'.$search.'%')
                    ->orWhere('state_code', 'like', '%'.$search.'%')
                    ->orWhere('city_code', 'like', '%'.$search.'%');
            });
        }

        $paginator = $query
            ->orderBy('priority')
            ->latest('id')
            ->paginate($this->perPage($request))
            ->appends($request->query());

        $items = $paginator->getCollection();
        $branchCodes = Branch::query()
            ->whereIn('id', $items->pluck('branch_id')->filter()->unique()->values())
            ->pluck('code', 'id')
            ->all();

        return response()->json([
            'data' => $items->map(
                fn (TaxRule $rule) => $this->mapDetail($rule, $branchCodes[(int) $rule->branch_id] ?? null)
            )->values(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ]);
    }

    public function show(string $taxRuleId): JsonResponse
    {
        $rule = $this->findRule($taxRuleId);
        $branchCode = Branch::query()->whereKey($rule->branch_id)->value('code');

        return response()->json([
            'data' => $this->mapDetail($rule, $branchCode),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $this->validatedPayload($request);

        $branch = Branch::query()
            ->where('code', $validated['branch_code'])
            ->firstOrFail();

        $rule = TaxRule::query()->create([
            'branch_id' => $branch->id,
            'country_code' => $validated['country_code'] ?? null,
            'state_code' => $validated['state_code'] ?? null,
            'city_code' => $validated['city_code'] ?? null,
            'fulfillment_type' => $validated['fulfillment_type'],
            'name' => $validated['name'],
            'rate_bps' => $this->toBps($validated['percentage_rate']),
            'applies_to_subtotal' => $validated['applies_to_subtotal'],
            'applies_to_service_fee' => $validated['applies_to_service_fee'],
            'applies_to_delivery_fee' => $validated['applies_to_delivery_fee'],
            'applies_to_small_order_fee' => $validated['applies_to_small_order_fee'],
            'priority' => $validated['priority'],
            'is_active' => $validated['is_active'],
        ]);

        app(AdminAuditLogger::class)->logAdminAction(
            action: 'tax_rule.create',
            entityType: 'tax_rule',
            entityPublicId: (string) $rule->id,
            entitySecondaryKey: $rule->name,
            summary: sprintf('Tax rule %s created.', $rule->name),
            requestSnapshot: $request->all(),
            contextSnapshot: [
                'branch_id' => $rule->branch_id,
                'branch_code' => $branch->code,
                'fulfillment_type' => $rule->fulfillment_type,
                'rate_bps' => $rule->rate_bps,
                'is_active' => (bool) $rule->is_active,
            ],
        );

        return response()->json([
            'message' => 'Tax rule created successfully.',
            'data' => $this->mapDetail($rule, $branch->code),
        ], 201);
    }

    public function update(Request $request, string $taxRuleId): JsonResponse
    {
        $rule = $this->findRule($taxRuleId);
        $validated = $this->validatedPayload($request, true);

        $branch = null;

        if (array_key_exists('branch_code', $validated)) {
            $branch = Branch::query()
                ->where('code', $validated['branch_code'])
                ->firstOrFail();

            $rule->branch_id = $branch->id;
        }

        if (array_key_exists('country_code', $validated)) {
            $rule->country_code = $validated['country_code'];
        }

        if (array_key_exists('state_code', $validated)) {
            $rule->state_code = $validated['state_code'];
        }

        if (array_key_exists('city_code', $validated)) {
            $rule->city_code = $validated['city_code'];
        }

        if (array_key_exists('fulfillment_type', $validated)) {
            $rule->fulfillment_type = $validated['fulfillment_type'];
        }

        if (array_key_exists('name', $validated)) {
            $rule->name = $validated['name'];
        }

        if (array_key_exists('percentage_rate', $validated)) {
            $rule->rate_bps = $this->toBps($validated['percentage_rate']);
        }

        foreach ([
            'applies_to_subtotal',
            'applies_to_service_fee',
            'applies_to_delivery_fee',
            'applies_to_small_order_fee',
            'priority',
            'is_active',
        ] as $field) {
            if (array_key_exists($field, $validated)) {
                $rule->{$field} = $validated[$field];
            }
        }

        $rule->save();

        $branchCode = $branch?->code
          ?? Branch::query()->whereKey($rule->branch_id)->value('code');

        app(AdminAuditLogger::class)->logAdminAction(
            action: 'tax_rule.update',
            entityType: 'tax_rule',
            entityPublicId: (string) $rule->id,
            entitySecondaryKey: $rule->name,
            summary: sprintf('Tax rule %s updated.', $rule->name),
            requestSnapshot: $request->all(),
            contextSnapshot: [
                'branch_id' => $rule->branch_id,
                'branch_code' => $branchCode,
                'fulfillment_type' => $rule->fulfillment_type,
                'rate_bps' => $rule->rate_bps,
                'is_active' => (bool) $rule->is_active,
            ],
        );

        return response()->json([
            'message' => 'Tax rule updated successfully.',
            'data' => $this->mapDetail($rule->refresh(), $branchCode),
        ]);
    }

    private function findRule(string $taxRuleId): TaxRule
    {
        $normalized = trim($taxRuleId);

        if (! ctype_digit($normalized)) {
            abort(404, 'Tax rule not found.');
        }

        return TaxRule::query()->findOrFail((int) $normalized);
    }

    private function validatedPayload(Request $request, bool $isUpdate = false): array
    {
        $required = $isUpdate ? 'sometimes' : 'required';

        $data = Validator::make($request->all(), [
            'branch_code' => [$required, 'string', Rule::exists('branches', 'code')],
            'country_code' => [$isUpdate ? 'sometimes' : 'nullable', 'nullable', 'string', 'size:2'],
            'state_code' => [$isUpdate ? 'sometimes' : 'nullable', 'nullable', 'string', 'max:40'],
            'city_code' => [$isUpdate ? 'sometimes' : 'nullable', 'nullable', 'string', 'max:40'],
            'fulfillment_type' => [$required, Rule::in((array) config('reference_settings.tax_rules.fulfillment_types', []))],
            'name' => [$required, 'string', 'max:120'],
            'percentage_rate' => [$required, 'numeric', 'min:0', 'max:100'],
            'applies_to_subtotal' => [$isUpdate ? 'sometimes' : 'nullable', 'boolean'],
            'applies_to_service_fee' => [$isUpdate ? 'sometimes' : 'nullable', 'boolean'],
            'applies_to_delivery_fee' => [$isUpdate ? 'sometimes' : 'nullable', 'boolean'],
            'applies_to_small_order_fee' => [$isUpdate ? 'sometimes' : 'nullable', 'boolean'],
            'priority' => [$isUpdate ? 'sometimes' : 'nullable', 'integer', 'min:0'],
            'is_active' => [$isUpdate ? 'sometimes' : 'nullable', 'boolean'],
        ])->validate();

        foreach (['branch_code', 'country_code', 'state_code', 'city_code'] as $key) {
            if (array_key_exists($key, $data) && $data[$key] !== null) {
                $data[$key] = strtoupper(trim((string) $data[$key]));
            }
        }

        if (! $isUpdate) {
            $data['applies_to_subtotal'] ??= true;
            $data['applies_to_service_fee'] ??= false;
            $data['applies_to_delivery_fee'] ??= false;
            $data['applies_to_small_order_fee'] ??= false;
            $data['priority'] ??= 0;
            $data['is_active'] ??= true;
        }

        return $data;
    }

    private function mapDetail(TaxRule $rule, ?string $branchCode): array
    {
        return [
            'id' => (string) $rule->id,
            'branch_id' => $rule->branch_id,
            'branch_code' => $branchCode,
            'country_code' => $rule->country_code,
            'state_code' => $rule->state_code,
            'city_code' => $rule->city_code,
            'fulfillment_type' => $rule->fulfillment_type,
            'name' => $rule->name,
            'rate_bps' => $rule->rate_bps,
            'percentage_rate' => $rule->rate_bps !== null ? round($rule->rate_bps / 100, 2) : null,
            'applies_to_subtotal' => (bool) $rule->applies_to_subtotal,
            'applies_to_service_fee' => (bool) $rule->applies_to_service_fee,
            'applies_to_delivery_fee' => (bool) $rule->applies_to_delivery_fee,
            'applies_to_small_order_fee' => (bool) $rule->applies_to_small_order_fee,
            'priority' => $rule->priority,
            'is_active' => (bool) $rule->is_active,
            'created_at' => optional($rule->created_at)?->toIso8601String(),
            'updated_at' => optional($rule->updated_at)?->toIso8601String(),
        ];
    }

    private function toBps(mixed $value): int
    {
        return (int) round(((float) $value) * 100);
    }

    private function toBool(mixed $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    private function perPage(Request $request): int
    {
        $default = (int) config('reference_settings.pagination.default_per_page', 15);
        $max = (int) config('reference_settings.pagination.max_per_page', 50);
        $requested = max(1, (int) $request->integer('per_page', $default));

        return min($requested, $max);
    }
}
