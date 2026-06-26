<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\DeliveryZone;
use App\Models\FeeRule;
use App\Models\TaxRule;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

final class PricingRulesSeeder extends Seeder
{
    public function run(): void
    {
        $branch = Branch::query()->where('code', 'MAIN')->firstOrFail();

        TaxRule::query()->updateOrCreate(
            [
                'branch_id' => $branch->id,
                'country_code' => 'US',
                'state_code' => 'NY',
                'city_code' => 'NYC',
                'fulfillment_type' => null,
                'name' => 'NYC Demo Sales Tax',
            ],
            [
                'rate_bps' => 887,
                'applies_to_subtotal' => true,
                'applies_to_service_fee' => false,
                'applies_to_delivery_fee' => false,
                'applies_to_small_order_fee' => false,
                'priority' => 10,
                'is_active' => true,
            ],
        );

        FeeRule::query()->updateOrCreate(
            ['code' => 'SERVICE_FEE_STANDARD'],
            [
                'branch_id' => $branch->id,
                'name' => 'Standard Service Fee',
                'fee_kind' => 'service_fee',
                'fulfillment_type' => null,
                'calculation_type' => 'bps',
                'fixed_amount' => null,
                'percentage_bps' => 500,
                'threshold_amount' => null,
                'min_amount' => 100,
                'max_amount' => 399,
                'taxable' => false,
                'conditions_json' => [
                    'demo' => true,
                    'description' => '5% capped demo service fee.',
                ],
                'priority' => 20,
                'is_active' => true,
            ],
        );

        FeeRule::query()->updateOrCreate(
            ['code' => 'DELIVERY_FEE_STANDARD'],
            [
                'branch_id' => $branch->id,
                'name' => 'Standard Delivery Fee',
                'fee_kind' => 'delivery_fee',
                'fulfillment_type' => 'delivery',
                'calculation_type' => 'fixed',
                'fixed_amount' => 399,
                'percentage_bps' => null,
                'threshold_amount' => null,
                'min_amount' => null,
                'max_amount' => null,
                'taxable' => false,
                'conditions_json' => [
                    'demo' => true,
                    'zone_code' => 'LOCAL',
                ],
                'priority' => 30,
                'is_active' => true,
            ],
        );

        FeeRule::query()->updateOrCreate(
            ['code' => 'SMALL_ORDER_FEE'],
            [
                'branch_id' => $branch->id,
                'name' => 'Small Order Fee',
                'fee_kind' => 'small_order_fee',
                'fulfillment_type' => 'delivery',
                'calculation_type' => 'fixed',
                'fixed_amount' => 250,
                'percentage_bps' => null,
                'threshold_amount' => 1500,
                'min_amount' => null,
                'max_amount' => null,
                'taxable' => false,
                'conditions_json' => [
                    'demo' => true,
                    'applies_when_subtotal_below' => 1500,
                ],
                'priority' => 40,
                'is_active' => true,
            ],
        );

        foreach ($this->deliveryZones($branch->code) as $zone) {
            DeliveryZone::query()->updateOrCreate(
                [
                    'branch_code' => $zone['branch_code'],
                    'code' => $zone['code'],
                ],
                [
                    'public_id' => DeliveryZone::query()
                        ->where('branch_code', $zone['branch_code'])
                        ->where('code', $zone['code'])
                        ->value('public_id') ?? (string) Str::uuid(),
                    'name' => $zone['name'],
                    'status' => 'active',
                    'pricing_strategy' => $zone['pricing_strategy'],
                    'minimum_order_amount' => $zone['minimum_order_amount'],
                    'base_fee_amount' => $zone['base_fee_amount'],
                    'fee_per_km_amount' => $zone['fee_per_km_amount'],
                    'free_delivery_threshold_amount' => $zone['free_delivery_threshold_amount'],
                    'estimated_minutes' => $zone['estimated_minutes'],
                    'sort_order' => $zone['sort_order'],
                    'meta' => [
                        'demo' => true,
                        'source' => 'seeder',
                        'description' => $zone['description'],
                    ],
                ],
            );
        }
    }

    private function deliveryZones(string $branchCode): array
    {
        return [
            [
                'branch_code' => $branchCode,
                'code' => 'LOCAL',
                'name' => 'Local Demo Zone',
                'pricing_strategy' => 'zone',
                'minimum_order_amount' => 1200,
                'base_fee_amount' => 399,
                'fee_per_km_amount' => null,
                'free_delivery_threshold_amount' => 4500,
                'estimated_minutes' => 25,
                'sort_order' => 1,
                'description' => 'Primary demo delivery zone around the main branch.',
            ],
            [
                'branch_code' => $branchCode,
                'code' => 'EXTENDED',
                'name' => 'Extended Demo Zone',
                'pricing_strategy' => 'hybrid',
                'minimum_order_amount' => 2000,
                'base_fee_amount' => 699,
                'fee_per_km_amount' => 120,
                'free_delivery_threshold_amount' => 6500,
                'estimated_minutes' => 40,
                'sort_order' => 2,
                'description' => 'Extended demo zone for higher delivery fee simulation.',
            ],
        ];
    }
}
