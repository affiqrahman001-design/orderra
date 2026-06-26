<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\DeliveryZone;
use App\Services\Admin\AdminAuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

final class AdminDeliveryZoneController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = DeliveryZone::query();

        if ($branchCode = $request->string('branch_code')->toString()) {
            $query->where('branch_code', strtoupper($branchCode));
        }

        if ($status = $request->string('status')->toString()) {
            $query->where('status', $status);
        }

        if ($search = trim($request->string('q')->toString())) {
            $query->where(function ($builder) use ($search): void {
                $builder
                    ->where('public_id', 'like', '%'.$search.'%')
                    ->orWhere('code', 'like', '%'.$search.'%')
                    ->orWhere('name', 'like', '%'.$search.'%')
                    ->orWhere('branch_code', 'like', '%'.$search.'%');
            });
        }

        $paginator = $query
            ->orderBy('branch_code')
            ->orderBy('sort_order')
            ->latest('id')
            ->paginate($this->perPage($request))
            ->appends($request->query());

        return response()->json([
            'data' => $paginator->getCollection()->map(
                fn (DeliveryZone $zone) => $this->mapDetail($zone)
            )->values(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ]);
    }

    public function show(string $deliveryZoneId): JsonResponse
    {
        return response()->json([
            'data' => $this->mapDetail($this->findZone($deliveryZoneId)),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $this->validatedPayload($request);

        $zone = DeliveryZone::query()->create($validated);

        app(AdminAuditLogger::class)->logAdminAction(
            action: 'delivery_zone.create',
            entityType: 'delivery_zone',
            entityPublicId: $zone->public_id,
            entitySecondaryKey: $zone->code,
            summary: sprintf('Delivery zone %s created.', $zone->code),
            requestSnapshot: $request->all(),
            contextSnapshot: [
                'branch_code' => $zone->branch_code,
                'status' => $zone->status,
                'pricing_strategy' => $zone->pricing_strategy,
            ],
        );

        return response()->json([
            'message' => 'Delivery zone created successfully.',
            'data' => $this->mapDetail($zone),
        ], 201);
    }

    public function update(Request $request, string $deliveryZoneId): JsonResponse
    {
        $zone = $this->findZone($deliveryZoneId);
        $validated = $this->validatedPayload($request, true, $zone);

        $zone->fill($validated);
        $zone->save();

        app(AdminAuditLogger::class)->logAdminAction(
            action: 'delivery_zone.update',
            entityType: 'delivery_zone',
            entityPublicId: $zone->public_id,
            entitySecondaryKey: $zone->code,
            summary: sprintf('Delivery zone %s updated.', $zone->code),
            requestSnapshot: $request->all(),
            contextSnapshot: [
                'branch_code' => $zone->branch_code,
                'status' => $zone->status,
                'pricing_strategy' => $zone->pricing_strategy,
            ],
        );

        return response()->json([
            'message' => 'Delivery zone updated successfully.',
            'data' => $this->mapDetail($zone->refresh()),
        ]);
    }

    private function findZone(string $deliveryZoneId): DeliveryZone
    {
        return DeliveryZone::query()
            ->where('public_id', $deliveryZoneId)
            ->firstOrFail();
    }

    private function validatedPayload(Request $request, bool $isUpdate = false, ?DeliveryZone $zone = null): array
    {
        $required = $isUpdate ? 'sometimes' : 'required';
        $branchCode = strtoupper(trim((string) $request->input('branch_code', $zone?->branch_code ?? '')));

        $data = Validator::make($request->all(), [
            'branch_code' => [$required, 'string', Rule::exists('branches', 'code')],
            'code' => [
                $required,
                'string',
                'max:40',
                Rule::unique('delivery_zones', 'code')
                    ->where(fn ($query) => $query->where('branch_code', $branchCode))
                    ->ignore($zone?->id),
            ],
            'name' => [$required, 'string', 'max:120'],
            'status' => [$isUpdate ? 'sometimes' : 'nullable', Rule::in((array) config('reference_settings.delivery_zones.statuses', []))],
            'pricing_strategy' => [$isUpdate ? 'sometimes' : 'nullable', Rule::in((array) config('reference_settings.delivery_zones.pricing_strategies', []))],
            'minimum_order_amount' => [$isUpdate ? 'sometimes' : 'nullable', 'nullable', 'numeric', 'min:0'],
            'base_fee_amount' => [$required, 'numeric', 'min:0'],
            'fee_per_km_amount' => [$isUpdate ? 'sometimes' : 'nullable', 'nullable', 'numeric', 'min:0'],
            'free_delivery_threshold_amount' => [$isUpdate ? 'sometimes' : 'nullable', 'nullable', 'numeric', 'min:0'],
            'estimated_minutes' => [$isUpdate ? 'sometimes' : 'nullable', 'nullable', 'integer', 'min:0'],
            'sort_order' => [$isUpdate ? 'sometimes' : 'nullable', 'integer', 'min:0'],
            'meta' => [$isUpdate ? 'sometimes' : 'nullable', 'array'],
        ])->validate();

        if (array_key_exists('branch_code', $data)) {
            $data['branch_code'] = strtoupper(trim((string) $data['branch_code']));
        }

        if (array_key_exists('code', $data)) {
            $data['code'] = strtoupper(trim((string) $data['code']));
        }

        $moneyFields = [
            'minimum_order_amount',
            'base_fee_amount',
            'fee_per_km_amount',
            'free_delivery_threshold_amount',
        ];

        foreach ($moneyFields as $field) {
            if (array_key_exists($field, $data) && $data[$field] !== null) {
                $data[$field] = $this->toCents($data[$field]);
            }
        }

        if (! $isUpdate) {
            $data['status'] ??= 'active';
            $data['pricing_strategy'] ??= 'hybrid';
            $data['sort_order'] ??= 0;
        }

        return $data;
    }

    private function mapDetail(DeliveryZone $zone): array
    {
        return [
            'id' => $zone->public_id,
            'branch_code' => $zone->branch_code,
            'code' => $zone->code,
            'name' => $zone->name,
            'status' => $zone->status,
            'pricing_strategy' => $zone->pricing_strategy,
            'minimum_order_amount' => $this->toMoney($zone->minimum_order_amount),
            'base_fee_amount' => $this->toMoney($zone->base_fee_amount),
            'fee_per_km_amount' => $this->toMoney($zone->fee_per_km_amount),
            'free_delivery_threshold_amount' => $this->toMoney($zone->free_delivery_threshold_amount),
            'estimated_minutes' => $zone->estimated_minutes,
            'sort_order' => $zone->sort_order,
            'meta' => $zone->meta ?? [],
            'created_at' => optional($zone->created_at)?->toIso8601String(),
            'updated_at' => optional($zone->updated_at)?->toIso8601String(),
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

    private function perPage(Request $request): int
    {
        $default = (int) config('reference_settings.pagination.default_per_page', 15);
        $max = (int) config('reference_settings.pagination.max_per_page', 50);
        $requested = max(1, (int) $request->integer('per_page', $default));

        return min($requested, $max);
    }
}
