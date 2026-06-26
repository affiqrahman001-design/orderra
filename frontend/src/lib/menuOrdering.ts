import type { Category } from '../contracts/category';
import type { Product } from '../contracts/product';

const CATEGORY_TAB_ORDER = ['burgers', 'combos', 'plates-pasta', 'sides', 'desserts', 'drinks'];

const ALL_MENU_CATEGORY_ORDER = [
  'combos',
  'burgers',
  'plates-pasta',
  'sides',
  'desserts',
  'drinks',
];

const PRODUCT_ORDER = [
  'signature-combo-basket',

  'signature-smash-burger',
  'smokehouse-prime-burger',
  'hot-honey-chicken-burger',

  'charred-beef-bowl',
  'teriyaki-salmon-bowl',
  'truffle-mushroom-pasta',
  'pomodoro-rigatoni',

  'parmesan-fries',
  'burnt-butter-corn',

  'basque-cheesecake',
  'dark-chocolate-tart',

  'classic-cola',
  'citrus-sparkling-cooler',
  'house-cappuccino',
  'still-water',
];

function getRank(value: string, order: string[]) {
  const index = order.indexOf(value);
  return index === -1 ? 999 : index;
}

function getProductRank(product: Product) {
  const slugRank = getRank(product.slug, PRODUCT_ORDER);
  if (slugRank !== 999) return slugRank;

  return getRank(product.categoryId, ALL_MENU_CATEGORY_ORDER) * 100;
}

export function sortCategoriesForMenu(categories: Category[]) {
  return [...categories].sort((a, b) => {
    const categoryRank = getRank(a.id, CATEGORY_TAB_ORDER) - getRank(b.id, CATEGORY_TAB_ORDER);
    if (categoryRank !== 0) return categoryRank;

    return a.name.localeCompare(b.name);
  });
}

export function sortProductsForMenu(products: Product[], selectedCategoryId: string) {
  const categoryOrder = selectedCategoryId === 'all' ? ALL_MENU_CATEGORY_ORDER : CATEGORY_TAB_ORDER;

  return [...products].sort((a, b) => {
    const categoryRank =
      getRank(a.categoryId, categoryOrder) - getRank(b.categoryId, categoryOrder);

    if (categoryRank !== 0) return categoryRank;

    const productRank = getProductRank(a) - getProductRank(b);
    if (productRank !== 0) return productRank;

    return a.name.localeCompare(b.name);
  });
}
