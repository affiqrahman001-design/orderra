<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\FeeRule;
use App\Services\Admin\AdminAuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

final class AdminFeeRuleController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = FeeRule::query();

        if ($branchCode = strtoupper(trim($request->string('branch_code')->toString()))) {
            $branch = Branch::query()->where('code', $branchCode)->first();
            $query->where('branch_id', $branch?->id ?? 0);
        }

        if ($feeKind = $request->string('fee_kind')->toString()) {
            $query->where('fee_kind', $feeKind);
        }

        if ($fulfillmentType = $request->string('fulfillment_type')->toString()) {
            $query->where('fulfillment_type', $fulfillmentType);
        }

        if ($calculationType = $request->string('calculation_type')->toString()) {
            $query->where('calculation_type', $calculationType);
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $this->toBool($request->input('is_active')));
        }

        if ($search = trim($request->string('q')->toString())) {
            $query->where(function ($builder) use ($search): void {
                $builder
                    ->where('code', 'like', '%'.$search.'%')
                    ->orWhere('name', 'like', '%'.$search.'%')
                    ->orWhere('fee_kind', 'like', '%'.$search.'%')
                    ->orWhere('fulfillment_type', 'like', '%'.$search.'%');
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
                fn (FeeRule $rule) => $this->mapDetail($rule, $branchCodes[(int) $rule->branch_id] ?? null)
            )->values(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ]);
    }

    public function show(string $feeRuleId): JsonResponse
    {
        $rule = $this->findRule($feeRuleId);
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

        $rule = FeeRule::query()->create([
            'branch_id' => $branch->id,
            'code' => $validated['code'],
            'name' => $validated['name'],
            'fee_kind' => $validated['fee_kind'],
            'fulfillment_type' => $validated['fulfillment_type'] ?? null,
            'calculation_type' => $validated['calculation_type'],
            'fixed_amount' => $validated['fixed_amount'] ?? null,
            'percentage_bps' => $validated['percentage_bps'] ?? null,
            'threshold_amount' => $validated['threshold_amount'] ?? null,
            'min_amount' => $validated['min_amount'] ?? null,
            'max_amount' => $validated['max_amount'] ?? null,
            'taxable' => $validated['taxable'],
            'conditions_json' => $validated['conditions_json'] ?? null,
            'priority' => $validated['priority'],
            'is_active' => $validated['is_active'],
        ]);

        app(AdminAuditLogger::class)->logAdminAction(
            action: 'fee_rule.create',
            entityType: 'fee_rule',
            entityPublicId: (string) $rule->id,
            entitySecondaryKey: $rule->code,
            summary: sprintf('Fee rule %s created.', $rule->code),
            requestSnapshot: $request->all(),
            contextSnapshot: [
                'branch_id' => $rule->branch_id,
                'branch_code' => $branch->code,
                'fee_kind' => $rule->fee_kind,
                'fulfillment_type' => $rule->fulfillment_type,
                'calculation_type' => $rule->calculation_type,
                'is_active' => (bool) $rule->is_active,
            ],
        );

        return response()->json([
            'message' => 'Fee rule created successfully.',
            'data' => $this->mapDetail($rule, $branch->code),
        ], 201);
    }

    public function update(Request $request, string $feeRuleId): JsonResponse
    {
        $rule = $this->findRule($feeRuleId);
        $validated = $this->validatedPayload($request, true, $rule);

        $branch = null;

        if (array_key_exists('branch_code', $validated)) {
            $branch = Branch::query()
                ->where('code', $validated['branch_code'])
                ->firstOrFail();

            $rule->branch_id = $branch->id;
        }

        foreach ([
            'code',
            'name',
            'fee_kind',
            'fulfillment_type',
            'calculation_type',
            'fixed_amount',
            'percentage_bps',
            'threshold_amount',
            'min_amount',
            'max_amount',
            'taxable',
            'conditions_json',
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
            action: 'fee_rule.update',
            entityType: 'fee_rule',
            entityPublicId: (string) $rule->id,
            entitySecondaryKey: $rule->code,
            summary: sprintf('Fee rule %s updated.', $rule->code),
            requestSnapshot: $request->all(),
            contextSnapshot: [
                'branch_id' => $rule->branch_id,
                'branch_code' => $branchCode,
                'fee_kind' => $rule->fee_kind,
                'fulfillment_type' => $rule->fulfillment_type,
                'calculation_type' => $rule->calculation_type,
                'is_active' => (bool) $rule->is_active,
            ],
        );

        return response()->json([
            'message' => 'Fee rule updated successfully.',
            'data' => $this->mapDetail($rule->refresh(), $branchCode),
        ]);
    }

    private function findRule(string $feeRuleId): FeeRule
    {
        $normalized = trim($feeRuleId);

        if (! ctype_digit($normalized)) {
            abort(404, 'Fee rule not found.');
        }

        return FeeRule::query()->findOrFail((int) $normalized);
    }

    private function validatedPayload(Request $request, bool $isUpdate = false, ?FeeRule $rule = null): array
    {
        $required = $isUpdate ? 'sometimes' : 'required';

        $data = Validator::make($request->all(), [
            'branch_code' => [$required, 'string', Rule::exists('branches', 'code')],
            'code' => [$required, 'string', 'max:40', Rule::unique('fee_rules', 'code')->ignore($rule?->id)],
            'name' => [$required, 'string', 'max:120'],
            'fee_kind' => [$required, Rule::in((array) config('reference_settings.fee_rules.fee_kinds', []))],
            'fulfillment_type' => [$isUpdate ? 'sometimes' : 'nullable', 'nullable', Rule::in((array) config('reference_settings.tax_rules.fulfillment_types', []))],
            'calculation_type' => [$required, Rule::in((array) config('reference_settings.fee_rules.calculation_types', []))],
            'fixed_amount' => [$isUpdate ? 'sometimes' : 'nullable', 'nullable', 'numeric', 'min:0'],
            'percentage_rate' => [$isUpdate ? 'sometimes' : 'nullable', 'nullable', 'numeric', 'min:0', 'max:100'],
            'threshold_amount' => [$isUpdate ? 'sometimes' : 'nullable', 'nullable', 'numeric', 'min:0'],
            'min_amount' => [$isUpdate ? 'sometimes' : 'nullable', 'nullable', 'numeric', 'min:0'],
            'max_amount' => [$isUpdate ? 'sometimes' : 'nullable', 'nullable', 'numeric', 'min:0'],
            'taxable' => [$isUpdate ? 'sometimes' : 'nullable', 'boolean'],
            'conditions_json' => [$isUpdate ? 'sometimes' : 'nullable', 'array'],
            'priority' => [$isUpdate ? 'sometimes' : 'nullable', 'integer', 'min:0'],
            'is_active' => [$isUpdate ? 'sometimes' : 'nullable', 'boolean'],
        ])->validate();

        foreach (['branch_code', 'code'] as $key) {
            if (array_key_exists($key, $data) && $data[$key] !== null) {
                $data[$key] = strtoupper(trim((string) $data[$key]));
            }
        }

        $calculationType = $data['calculation_type'] ?? $rule?->calculation_type;

        if ($calculationType === 'fixed') {
            $fixedAmount = $data['fixed_amount'] ?? null;

            if ($fixedAmount === null && ! $isUpdate) {
                abort(422, 'fixed_amount is required when calculation_type is fixed.');
            }

            $data['fixed_amount'] = $fixedAmount !== null ? $this->toCents($fixedAmount) : null;
            $data['percentage_bps'] = null;
            unset($data['percentage_rate']);
        }

        if ($calculationType === 'percentage') {
            $percentageRate = $data['percentage_rate'] ?? null;

            if ($percentageRate === null && ! $isUpdate) {
                abort(422, 'percentage_rate is required when calculation_type is percentage.');
            }

            $data['percentage_bps'] = $percentageRate !== null ? $this->toBps($percentageRate) : null;
            $data['fixed_amount'] = null;
            unset($data['percentage_rate']);
        }

        foreach (['threshold_amount', 'min_amount', 'max_amount'] as $field) {
            if (array_key_exists($field, $data) && $data[$field] !== null) {
                $data[$field] = $this->toCents($data[$field]);
            }
        }

        if (! $isUpdate) {
            $data['taxable'] ??= false;
            $data['priority'] ??= 0;
            $data['is_active'] ??= true;
        }

        return $data;
    }

    private function mapDetail(FeeRule $rule, ?string $branchCode): array
    {
        return [
            'id' => (string) $rule->id,
            'branch_id' => $rule->branch_id,
            'branch_code' => $branchCode,
            'code' => $rule->code,
            'name' => $rule->name,
            'fee_kind' => $rule->fee_kind,
            'fulfillment_type' => $rule->fulfillment_type,
            'calculation_type' => $rule->calculation_type,
            'fixed_amount' => $this->toMoney($rule->fixed_amount),
            'percentage_bps' => $rule->percentage_bps,
            'percentage_rate' => $rule->percentage_bps !== null ? round($rule->percentage_bps / 100, 2) : null,
            'threshold_amount' => $this->toMoney($rule->threshold_amount),
            'min_amount' => $this->toMoney($rule->min_amount),
            'max_amount' => $this->toMoney($rule->max_amount),
            'taxable' => (bool) $rule->taxable,
            'conditions_json' => $rule->conditions_json ?? [],
            'priority' => $rule->priority,
            'is_active' => (bool) $rule->is_active,
            'created_at' => optional($rule->created_at)?->toIso8601String(),
            'updated_at' => optional($rule->updated_at)?->toIso8601String(),
        ];
    }

    private function toCents(mixed $value): int
    {
        return (int) round(((float) $value) * 100);
    }

    private function toMoney(?int $value): ?float
    {
        return $value === null ? null : round($value / 100, 2);
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
