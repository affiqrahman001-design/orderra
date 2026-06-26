import type { Category } from '../../../contracts/category';
import type { Product, ProductFlow, ProductOptionGroup } from '../../../contracts/product';
import { apiRequest } from '../../../lib/api/client';
import { resolveMenuImage } from '../../../lib/menuAssets';
import type { CatalogAdapter } from '../../catalogService';

type BackendCategory = {
  id: string;
  slug: string;
  name: string;
  description?: string | null;
  item_count?: number;
  itemCount?: number;
};

type BackendModifierOption = {
  id: string;
  name?: string;
  label?: string;
  price_delta?: number;
  price_delta_amount?: number;
};

type BackendModifierGroup = {
  id: string;
  name?: string;
  label?: string;
  helper_text?: string | null;
  selection_mode?: 'single' | 'multiple' | 'text';
  is_required?: boolean;
  required?: boolean;
  options?: BackendModifierOption[];
};

type BackendProduct = {
  id: string;
  slug: string;
  name: string;
  short_name?: string | null;
  description: string;
  price: number;
  currency?: string;
  image_url?: string | null;
  category_slug?: string;
  category_id?: string;
  badge_label?: string | null;
  flow?: ProductFlow;
  is_available?: boolean;
  featured?: boolean;
  prep_note?: string | null;
  modifier_groups?: BackendModifierGroup[];
};

type BackendEnvelope<T> = { data: T };

function mapCategory(category: BackendCategory): Category {
  const itemCount = category.item_count ?? category.itemCount ?? 0;
  return {
    id: category.slug ?? category.id,
    name: category.name,
    description: category.description ?? 'Premium ORDERra menu category.',
    itemCountLabel: itemCount > 0 ? `${itemCount} items` : 'Menu items',
  };
}

function normalizePrice(value: number | undefined): number {
  if (!value) return 0;
  return value > 100 ? value / 100 : value;
}

function mapOptionGroups(groups: BackendModifierGroup[] | undefined): ProductOptionGroup[] {
  return (groups ?? []).map((group) => ({
    id: group.id,
    label: group.label ?? group.name ?? 'Options',
    helperText: group.helper_text ?? undefined,
    selectionMode: group.selection_mode ?? 'single',
    required: group.required ?? group.is_required ?? false,
    options: (group.options ?? []).map((option) => ({
      id: option.id,
      label: option.label ?? option.name ?? 'Option',
      priceDelta: normalizePrice(option.price_delta ?? option.price_delta_amount),
    })),
  }));
}

function mapProduct(item: BackendProduct): Product {
  const image = resolveMenuImage(item.slug, item.image_url ?? null);
  return {
    id: item.id,
    slug: item.slug,
    name: item.name,
    shortName: item.short_name ?? item.name,
    description: item.description,
    price: normalizePrice(item.price),
    image,
    imageAlt: item.name,
    categoryId: item.category_slug ?? item.category_id ?? 'all',
    badge: item.badge_label ?? undefined,
    flow: item.flow ?? ((item.modifier_groups?.length ?? 0) > 0 ? 'full' : 'none'),
    available: item.is_available ?? true,
    featured: item.featured ?? false,
    prepNote: item.prep_note ?? undefined,
    optionGroups: mapOptionGroups(item.modifier_groups),
  };
}

let cachedProducts: Product[] | null = null;

export const catalogHttpAdapter: CatalogAdapter = {
  async listCategories() {
    const response = await apiRequest<BackendEnvelope<BackendCategory[]>>('/catalog/categories');
    return response.data.map(mapCategory);
  },

  async listProducts(): Promise<Product[]> {
    const response = await apiRequest<BackendEnvelope<BackendProduct[]>>('/catalog/items');
    const products = response.data.map(mapProduct);

    cachedProducts = products;

    return products;
  },

  async getProductById(productId) {
    if (cachedProducts) {
      const product = cachedProducts.find(
        (entry) => entry.id === productId || entry.slug === productId,
      );
      if (product) return product;
    }

    const response = await apiRequest<BackendEnvelope<BackendProduct>>(
      `/catalog/items/${productId}`,
    );
    return mapProduct(response.data);
  },
};
