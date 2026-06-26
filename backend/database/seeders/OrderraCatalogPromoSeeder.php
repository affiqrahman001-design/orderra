<?php declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\MenuCategory;
use App\Models\MenuItem;
use App\Models\ModifierGroup;
use App\Models\ModifierOption;
use App\Models\Promo;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

final class OrderraCatalogPromoSeeder extends Seeder
{
    public function run(): void
    {
        $branch = Branch::query()->where('code', 'MAIN')->firstOrFail();

        $categories = $this->seedCategories();

        $this->deactivateRetiredCatalogItems();

        $signature = $this->seedItem($branch, $categories['BURGERS'], [
            'code' => 'SIGNATURE_SMASH_BURGER',
            'name' => 'Signature Smash Burger',
            'short_name' => 'Signature Smash',
            'slug' => 'signature-smash-burger',
            'description' => 'Triple smashed beef patties, melted American cheese, house sauce, pickles, and toasted brioche.',
            'base_price_amount' => 2800,
            'image_url' => '/media/menu/signature-smash-burger.webp',
            'is_featured' => true,
            'badge_label' => 'Signature',
            'prep_note' => 'Smashed fresh on the grill.',
            'product_flow' => 'full',
            'sort_order' => 1,
        ]);

        $hotHoney = $this->seedItem($branch, $categories['BURGERS'], [
            'code' => 'HOT_HONEY_CHICKEN_BURGER',
            'name' => 'Hot Honey Chicken Burger',
            'short_name' => 'Hot Honey Chicken',
            'slug' => 'hot-honey-chicken-burger',
            'description' => 'Crispy chicken fillet, hot honey glaze, premium slaw, pickles, and soft brioche bun.',
            'base_price_amount' => 2600,
            'image_url' => '/media/menu/hot-honey-chicken-burger.webp',
            'is_featured' => true,
            'badge_label' => 'Popular',
            'prep_note' => 'Best served hot.',
            'product_flow' => 'full',
            'sort_order' => 2,
        ]);

        $smokehouse = $this->seedItem($branch, $categories['BURGERS'], [
            'code' => 'SMOKEHOUSE_PRIME_BURGER',
            'name' => 'Smokehouse Prime Burger',
            'short_name' => 'Smokehouse Prime',
            'slug' => 'smokehouse-prime-burger',
            'description' => 'Flame-grilled beef burger with smoked sauce, aged cheddar, caramelized onion, and crisp lettuce.',
            'base_price_amount' => 3000,
            'image_url' => '/media/menu/smokehouse-prime-burger.webp',
            'is_featured' => true,
            'badge_label' => 'Best Seller',
            'prep_note' => 'Freshly grilled to order.',
            'product_flow' => 'full',
            'sort_order' => 3,
        ]);

        $this->seedItem($branch, $categories['COMBOS'], [
            'code' => 'SIGNATURE_COMBO_BASKET',
            'name' => 'Signature Combo Basket',
            'short_name' => 'Combo Basket',
            'slug' => 'signature-combo-basket',
            'description' => 'Signature burger, parmesan fries, and a cold drink in one premium demo combo.',
            'base_price_amount' => 3900,
            'image_url' => '/media/menu/signature-combo-basket.webp',
            'is_featured' => true,
            'badge_label' => 'Combo',
            'prep_note' => 'Best value demo meal.',
            'product_flow' => 'combo',
            'sort_order' => 1,
        ]);

        $this->seedItem($branch, $categories['SIDES'], [
            'code' => 'PARMESAN_FRIES',
            'name' => 'Parmesan Fries',
            'short_name' => 'Parmesan Fries',
            'slug' => 'parmesan-fries',
            'description' => 'Crispy fries finished with parmesan, herbs, and sea salt.',
            'base_price_amount' => 1400,
            'image_url' => '/media/menu/parmesan-fries.webp',
            'is_featured' => false,
            'badge_label' => null,
            'prep_note' => 'Seasoned fresh after fry.',
            'product_flow' => 'simple',
            'sort_order' => 1,
        ]);

        $this->seedItem($branch, $categories['SIDES'], [
            'code' => 'BURNT_BUTTER_CORN',
            'name' => 'Burnt Butter Corn',
            'short_name' => 'Butter Corn',
            'slug' => 'burnt-butter-corn',
            'description' => 'Sweet corn tossed with burnt butter, herbs, and a light smoky finish.',
            'base_price_amount' => 1200,
            'image_url' => '/media/menu/burnt-butter-corn.webp',
            'is_featured' => false,
            'badge_label' => null,
            'prep_note' => 'Warm side dish.',
            'product_flow' => 'simple',
            'sort_order' => 2,
        ]);

        $this->seedItem($branch, $categories['DRINKS'], [
            'code' => 'CITRUS_SPARKLING_COOLER',
            'name' => 'Citrus Sparkling Cooler',
            'short_name' => 'Citrus Cooler',
            'slug' => 'citrus-sparkling-cooler',
            'description' => 'Cold citrus soda with mint and a cleaner, less sugary finish.',
            'base_price_amount' => 1100,
            'image_url' => '/media/menu/citrus-sparkling-cooler.webp',
            'is_featured' => false,
            'badge_label' => null,
            'prep_note' => 'Served chilled.',
            'product_flow' => 'simple',
            'sort_order' => 1,
        ]);

        $this->seedItem($branch, $categories['DRINKS'], [
            'code' => 'CLASSIC_COLA',
            'name' => 'Classic Cola',
            'short_name' => 'Cola',
            'slug' => 'classic-cola',
            'description' => 'Cold sparkling cola with ice, bubbles, and a crisp finish.',
            'base_price_amount' => 700,
            'image_url' => '/media/menu/classic-cola.webp',
            'is_featured' => false,
            'badge_label' => null,
            'prep_note' => 'Served cold.',
            'product_flow' => 'simple',
            'sort_order' => 2,
        ]);

        $this->seedItem($branch, $categories['DRINKS'], [
            'code' => 'STILL_WATER',
            'name' => 'Still Water',
            'short_name' => 'Water',
            'slug' => 'still-water',
            'description' => 'Clean still water for a simple chilled refreshment.',
            'base_price_amount' => 500,
            'image_url' => '/media/menu/still-water.webp',
            'is_featured' => false,
            'badge_label' => null,
            'prep_note' => 'Served chilled.',
            'product_flow' => 'simple',
            'sort_order' => 3,
        ]);

        $this->seedItem($branch, $categories['DRINKS'], [
            'code' => 'HOUSE_CAPPUCCINO',
            'name' => 'House Cappuccino',
            'short_name' => 'Cappuccino',
            'slug' => 'house-cappuccino',
            'description' => 'Balanced espresso, textured milk, and a smooth microfoam finish.',
            'base_price_amount' => 1200,
            'image_url' => '/media/menu/house-cappuccino.webp',
            'is_featured' => false,
            'badge_label' => null,
            'prep_note' => 'Hot drink.',
            'product_flow' => 'simple',
            'sort_order' => 4,
        ]);

        $this->seedItem($branch, $categories['PLATES'], [
            'code' => 'CHARRED_BEEF_BOWL',
            'name' => 'Charred Beef Bowl',
            'short_name' => 'Beef Bowl',
            'slug' => 'charred-beef-bowl',
            'description' => 'Charred beef, warm grains, and premium house toppings in a balanced bowl.',
            'base_price_amount' => 3100,
            'image_url' => '/media/menu/charred-beef-bowl.webp',
            'is_featured' => false,
            'badge_label' => null,
            'prep_note' => 'Kitchen bowl.',
            'product_flow' => 'simple',
            'sort_order' => 1,
        ]);

        $this->seedItem($branch, $categories['PLATES'], [
            'code' => 'TERIYAKI_SALMON_BOWL',
            'name' => 'Teriyaki Salmon Bowl',
            'short_name' => 'Salmon Bowl',
            'slug' => 'teriyaki-salmon-bowl',
            'description' => 'Teriyaki salmon with warm rice and clean premium toppings.',
            'base_price_amount' => 2700,
            'image_url' => '/media/menu/teriyaki-salmon-bowl.webp',
            'is_featured' => false,
            'badge_label' => null,
            'prep_note' => 'Warm bowl.',
            'product_flow' => 'simple',
            'sort_order' => 2,
        ]);

        $this->seedItem($branch, $categories['PLATES'], [
            'code' => 'POMODORO_RIGATONI',
            'name' => 'Pomodoro Rigatoni',
            'short_name' => 'Rigatoni',
            'slug' => 'pomodoro-rigatoni',
            'description' => 'Rigatoni pasta in a bright pomodoro sauce with a clean premium finish.',
            'base_price_amount' => 2400,
            'image_url' => '/media/menu/pomodoro-rigatoni.webp',
            'is_featured' => false,
            'badge_label' => null,
            'prep_note' => 'Pasta bowl.',
            'product_flow' => 'simple',
            'sort_order' => 3,
        ]);

        $this->seedItem($branch, $categories['PLATES'], [
            'code' => 'TRUFFLE_MUSHROOM_PASTA',
            'name' => 'Truffle Mushroom Pasta',
            'short_name' => 'Truffle Pasta',
            'slug' => 'truffle-mushroom-pasta',
            'description' => 'Creamy mushroom pasta with a light truffle finish.',
            'base_price_amount' => 3300,
            'image_url' => '/media/menu/truffle-mushroom-pasta.webp',
            'is_featured' => false,
            'badge_label' => null,
            'prep_note' => 'Pasta bowl.',
            'product_flow' => 'simple',
            'sort_order' => 4,
        ]);

        $this->seedItem($branch, $categories['DESSERTS'], [
            'code' => 'BASQUE_CHEESECAKE',
            'name' => 'Basque Cheesecake',
            'short_name' => 'Cheesecake',
            'slug' => 'basque-cheesecake',
            'description' => 'Creamy burnt-top cheesecake slice with a rich premium finish.',
            'base_price_amount' => 1800,
            'image_url' => '/media/menu/basque-cheesecake.webp',
            'is_featured' => false,
            'badge_label' => null,
            'prep_note' => 'Dessert portion.',
            'product_flow' => 'simple',
            'sort_order' => 1,
        ]);

        $this->seedItem($branch, $categories['DESSERTS'], [
            'code' => 'DARK_CHOCOLATE_TART',
            'name' => 'Dark Chocolate Tart',
            'short_name' => 'Chocolate Tart',
            'slug' => 'dark-chocolate-tart',
            'description' => 'Dark chocolate tart with a smooth rich filling and crisp pastry shell.',
            'base_price_amount' => 1900,
            'image_url' => '/media/menu/dark-chocolate-tart.webp',
            'is_featured' => false,
            'badge_label' => null,
            'prep_note' => 'Dessert portion.',
            'product_flow' => 'simple',
            'sort_order' => 2,
        ]);

        $this->seedBurgerModifiers($signature);
        $this->seedBurgerModifiers($hotHoney);
        $this->seedBurgerModifiers($smokehouse);

        $this->seedPromos();
    }

    private function seedCategories(): array
    {
        $definitions = [
            'BURGERS' => [
                'name' => 'Burgers',
                'slug' => 'burgers',
                'description' => 'Premium signature burger lineup.',
                'sort_order' => 1,
            ],
            'COMBOS' => [
                'name' => 'Combos',
                'slug' => 'combos',
                'description' => 'Premium meal bundles for demo checkout.',
                'sort_order' => 2,
            ],
            'PLATES' => [
                'name' => 'Plates & Pasta',
                'slug' => 'plates-pasta',
                'description' => 'Warm bowls and pasta plates for expanded demo ordering.',
                'sort_order' => 3,
            ],
            'SIDES' => [
                'name' => 'Sides',
                'slug' => 'sides',
                'description' => 'Crispy sides and warm add-ons.',
                'sort_order' => 4,
            ],
            'DESSERTS' => [
                'name' => 'Desserts',
                'slug' => 'desserts',
                'description' => 'Small premium desserts.',
                'sort_order' => 5,
            ],
            'DRINKS' => [
                'name' => 'Drinks',
                'slug' => 'drinks',
                'description' => 'Clean premium drinks selection.',
                'sort_order' => 6,
            ],
        ];

        $categories = [];

        foreach ($definitions as $code => $definition) {
            $categories[$code] = MenuCategory::query()->updateOrCreate(
                ['code' => $code],
                [
                    'public_id' => MenuCategory::query()
                        ->where('code', $code)
                        ->value('public_id') ?? (string) Str::uuid(),
                    'name' => $definition['name'],
                    'slug' => $definition['slug'],
                    'description' => $definition['description'],
                    'is_active' => true,
                    'sort_order' => $definition['sort_order'],
                    'meta' => [
                        'demo' => true,
                        'source' => 'seeder',
                    ],
                ],
            );
        }

        return $categories;
    }

    private function deactivateRetiredCatalogItems(): void
    {
        MenuItem::query()
            ->whereIn('code', [
                'PRIME_BURGER_COMBO',
            ])
            ->update([
                'is_active' => false,
            ]);
    }

    private function seedItem(Branch $branch, MenuCategory $category, array $item): MenuItem
    {
        return MenuItem::query()->updateOrCreate(
            ['code' => $item['code']],
            [
                'public_id' => MenuItem::query()
                    ->where('code', $item['code'])
                    ->value('public_id') ?? (string) Str::uuid(),
                'branch_id' => $branch->id,
                'menu_category_id' => $category->id,
                'name' => $item['name'],
                'short_name' => $item['short_name'],
                'slug' => $item['slug'],
                'description' => $item['description'],
                'base_price_amount' => $item['base_price_amount'],
                'currency' => $branch->currency,
                'image_url' => $item['image_url'],
                'is_active' => true,
                'is_featured' => $item['is_featured'],
                'badge_label' => $item['badge_label'],
                'prep_note' => $item['prep_note'],
                'product_flow' => $item['product_flow'],
                'sort_order' => $item['sort_order'],
                'meta' => [
                    'demo' => true,
                    'source' => 'seeder',
                    'branch_code' => $branch->code,
                ],
            ],
        );
    }

    private function seedBurgerModifiers(MenuItem $item): void
    {
        $addOns = ModifierGroup::query()->updateOrCreate(
            ['code' => $item->code . '_ADD_ONS'],
            [
                'public_id' => ModifierGroup::query()
                    ->where('code', $item->code . '_ADD_ONS')
                    ->value('public_id') ?? (string) Str::uuid(),
                'menu_item_id' => $item->id,
                'name' => 'Premium add-ons',
                'helper_text' => 'Optional premium extras.',
                'selection_mode' => 'multiple',
                'is_required' => false,
                'min_select' => 0,
                'max_select' => 4,
                'sort_order' => 1,
                'is_active' => true,
                'meta' => [
                    'demo' => true,
                    'source' => 'seeder',
                ],
            ],
        );

        $options = [
            ['code' => 'EXTRA_CHEESE', 'label' => 'Extra Cheese', 'price_delta_amount' => 150, 'sort_order' => 1],
            ['code' => 'EXTRA_PATTY', 'label' => 'Extra Patty', 'price_delta_amount' => 450, 'sort_order' => 2],
            ['code' => 'HOUSE_SAUCE', 'label' => 'Extra House Sauce', 'price_delta_amount' => 75, 'sort_order' => 3],
            ['code' => 'PICKLES', 'label' => 'Extra Pickles', 'price_delta_amount' => 50, 'sort_order' => 4],
        ];

        foreach ($options as $option) {
            ModifierOption::query()->updateOrCreate(
                [
                    'modifier_group_id' => $addOns->id,
                    'code' => $option['code'],
                ],
                [
                    'public_id' => ModifierOption::query()
                        ->where('modifier_group_id', $addOns->id)
                        ->where('code', $option['code'])
                        ->value('public_id') ?? (string) Str::uuid(),
                    'label' => $option['label'],
                    'price_delta_amount' => $option['price_delta_amount'],
                    'is_default' => false,
                    'sort_order' => $option['sort_order'],
                    'is_active' => true,
                    'meta' => [
                        'demo' => true,
                        'source' => 'seeder',
                    ],
                ],
            );
        }
    }

    private function seedPromos(): void
    {
        Promo::query()->updateOrCreate(
            ['code' => 'WELCOME10'],
            [
                'public_id' => Promo::query()->where('code', 'WELCOME10')->value('public_id') ?? (string) Str::uuid(),
                'title' => 'Welcome 10% Off',
                'description' => 'Enjoy 10% off for first demo checkout.',
                'discount_type' => 'percentage',
                'value_bps' => 1000,
                'fixed_amount' => null,
                'minimum_subtotal_amount' => 2000,
                'badge_label' => '10% OFF',
                'starts_at' => now()->subDay(),
                'ends_at' => now()->addMonths(3),
                'usage_limit' => null,
                'per_user_limit' => null,
                'is_active' => true,
                'meta' => [
                    'demo' => true,
                    'source' => 'seeder',
                ],
            ],
        );

        Promo::query()->updateOrCreate(
            ['code' => 'COMBO5'],
            [
                'public_id' => Promo::query()->where('code', 'COMBO5')->value('public_id') ?? (string) Str::uuid(),
                'title' => '$5 Off Combo Basket',
                'description' => 'Fixed discount demo promo for larger baskets.',
                'discount_type' => 'fixed',
                'value_bps' => null,
                'fixed_amount' => 500,
                'minimum_subtotal_amount' => 3000,
                'badge_label' => '$5 OFF',
                'starts_at' => now()->subDay(),
                'ends_at' => now()->addMonths(3),
                'usage_limit' => null,
                'per_user_limit' => null,
                'is_active' => true,
                'meta' => [
                    'demo' => true,
                    'source' => 'seeder',
                ],
            ],
        );
    }
}
