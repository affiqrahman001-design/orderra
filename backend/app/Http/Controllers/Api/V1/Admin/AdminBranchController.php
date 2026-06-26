<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Services\Admin\AdminAuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

final class AdminBranchController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Branch::query();

        if ($status = $request->string('status')->toString()) {
            $query->where('status', $status);
        }

        if ($search = trim($request->string('q')->toString())) {
            $query->where(function ($builder) use ($search): void {
                $builder
                    ->where('public_id', 'like', '%'.$search.'%')
                    ->orWhere('code', 'like', '%'.$search.'%')
                    ->orWhere('name', 'like', '%'.$search.'%')
                    ->orWhere('city', 'like', '%'.$search.'%')
                    ->orWhere('state', 'like', '%'.$search.'%');
            });
        }

        $paginator = $query
            ->orderByDesc('is_default')
            ->latest('id')
            ->paginate($this->perPage($request))
            ->appends($request->query());

        return response()->json([
            'data' => $paginator->getCollection()->map(
                fn (Branch $branch) => $this->mapSummary($branch)
            )->values(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ]);
    }

    public function show(string $branchId): JsonResponse
    {
        return response()->json([
            'data' => $this->mapDetail($this->findBranch($branchId)),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $this->validatedPayload($request);

        $branch = DB::transaction(function () use ($validated): Branch {
            if (($validated['is_default'] ?? false) === true) {
                Branch::query()->update(['is_default' => false]);
            }

            return Branch::query()->create($validated);
        });

        app(AdminAuditLogger::class)->logAdminAction(
            action: 'branch.create',
            entityType: 'branch',
            entityPublicId: $branch->public_id,
            entitySecondaryKey: $branch->code,
            summary: sprintf('Branch %s created.', $branch->code),
            requestSnapshot: $request->all(),
            contextSnapshot: [
                'status' => $branch->status,
                'country_code' => $branch->country_code,
                'currency' => $branch->currency,
                'timezone' => $branch->timezone,
            ],
        );

        return response()->json([
            'message' => 'Branch created successfully.',
            'data' => $this->mapDetail($branch),
        ], 201);
    }

    public function update(Request $request, string $branchId): JsonResponse
    {
        $branch = $this->findBranch($branchId);
        $validated = $this->validatedPayload($request, true, $branch);

        $branch = DB::transaction(function () use ($branch, $validated): Branch {
            if (($validated['is_default'] ?? false) === true) {
                Branch::query()
                    ->where('id', '!=', $branch->id)
                    ->update(['is_default' => false]);
            }

            $branch->fill($validated);
            $branch->save();

            return $branch->refresh();
        });

        app(AdminAuditLogger::class)->logAdminAction(
            action: 'branch.update',
            entityType: 'branch',
            entityPublicId: $branch->public_id,
            entitySecondaryKey: $branch->code,
            summary: sprintf('Branch %s updated.', $branch->code),
            requestSnapshot: $request->all(),
            contextSnapshot: [
                'status' => $branch->status,
                'country_code' => $branch->country_code,
                'currency' => $branch->currency,
                'timezone' => $branch->timezone,
                'is_default' => $branch->is_default,
            ],
        );

        return response()->json([
            'message' => 'Branch updated successfully.',
            'data' => $this->mapDetail($branch),
        ]);
    }

    private function findBranch(string $branchId): Branch
    {
        return Branch::query()
            ->where('public_id', $branchId)
            ->orWhere('code', strtoupper(trim($branchId)))
            ->firstOrFail();
    }

    private function validatedPayload(Request $request, bool $isUpdate = false, ?Branch $branch = null): array
    {
        $required = $isUpdate ? 'sometimes' : 'required';

        $data = Validator::make($request->all(), [
            'code' => [$required, 'string', 'max:40', Rule::unique('branches', 'code')->ignore($branch?->id)],
            'name' => [$required, 'string', 'max:120'],
            'status' => [$isUpdate ? 'sometimes' : 'nullable', Rule::in((array) config('reference_settings.branch.statuses', []))],
            'country_code' => [$required, 'string', 'size:2'],
            'currency' => [$required, 'string', 'size:3'],
            'timezone' => [$required, 'string', 'max:80'],
            'phone' => [$isUpdate ? 'sometimes' : 'nullable', 'nullable', 'string', 'max:40'],
            'email' => [$isUpdate ? 'sometimes' : 'nullable', 'nullable', 'email', 'max:120'],
            'address_line_1' => [$isUpdate ? 'sometimes' : 'nullable', 'nullable', 'string', 'max:160'],
            'address_line_2' => [$isUpdate ? 'sometimes' : 'nullable', 'nullable', 'string', 'max:160'],
            'city' => [$isUpdate ? 'sometimes' : 'nullable', 'nullable', 'string', 'max:120'],
            'state' => [$isUpdate ? 'sometimes' : 'nullable', 'nullable', 'string', 'max:120'],
            'postal_code' => [$isUpdate ? 'sometimes' : 'nullable', 'nullable', 'string', 'max:40'],
            'supports_delivery' => [$isUpdate ? 'sometimes' : 'nullable', 'boolean'],
            'supports_pickup' => [$isUpdate ? 'sometimes' : 'nullable', 'boolean'],
            'supports_dine_in' => [$isUpdate ? 'sometimes' : 'nullable', 'boolean'],
            'is_default' => [$isUpdate ? 'sometimes' : 'nullable', 'boolean'],
            'meta' => [$isUpdate ? 'sometimes' : 'nullable', 'array'],
        ])->validate();

        foreach (['code', 'country_code', 'currency'] as $key) {
            if (array_key_exists($key, $data)) {
                $data[$key] = strtoupper(trim((string) $data[$key]));
            }
        }

        if (! $isUpdate) {
            $data['status'] ??= 'active';
            $data['supports_delivery'] ??= true;
            $data['supports_pickup'] ??= true;
            $data['supports_dine_in'] ??= true;
            $data['is_default'] ??= false;
        }

        return $data;
    }

    private function mapSummary(Branch $branch): array
    {
        return [
            'id' => $branch->public_id,
            'code' => $branch->code,
            'name' => $branch->name,
            'status' => $branch->status,
            'country_code' => $branch->country_code,
            'currency' => $branch->currency,
            'timezone' => $branch->timezone,
            'supports_delivery' => (bool) $branch->supports_delivery,
            'supports_pickup' => (bool) $branch->supports_pickup,
            'supports_dine_in' => (bool) $branch->supports_dine_in,
            'is_default' => (bool) $branch->is_default,
            'city' => $branch->city,
            'state' => $branch->state,
            'created_at' => optional($branch->created_at)?->toIso8601String(),
        ];
    }

    private function mapDetail(Branch $branch): array
    {
        return [
            ...$this->mapSummary($branch),
            'phone' => $branch->phone,
            'email' => $branch->email,
            'address_line_1' => $branch->address_line_1,
            'address_line_2' => $branch->address_line_2,
            'postal_code' => $branch->postal_code,
            'meta' => $branch->meta ?? [],
            'updated_at' => optional($branch->updated_at)?->toIso8601String(),
        ];
    }

    private function perPage(Request $request): int
    {
        $default = (int) config('reference_settings.pagination.default_per_page', 15);
        $max = (int) config('reference_settings.pagination.max_per_page', 50);
        $requested = max(1, (int) $request->integer('per_page', $default));

        return min($requested, $max);
    }
}
