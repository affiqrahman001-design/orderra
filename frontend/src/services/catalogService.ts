import type { Category } from "../contracts/category";
import type { Product } from "../contracts/product";

export interface CatalogAdapter {
  listCategories(): Promise<Category[]>;
  listProducts(): Promise<Product[]>;
  getProductById(productId: string): Promise<Product | null>;
}

export interface CatalogService {
  listCategories(): Promise<Category[]>;
  listProducts(): Promise<Product[]>;
  getFeaturedProducts(): Promise<Product[]>;
  getProductById(productId: string): Promise<Product | null>;
}

export function createCatalogService(adapter: CatalogAdapter): CatalogService {
  return {
    listCategories: () => adapter.listCategories(),
    listProducts: () => adapter.listProducts(),
    async getFeaturedProducts() {
      const products = await adapter.listProducts();
      return products.filter((product) => product.featured);
    },
    getProductById: (productId) => adapter.getProductById(productId),
  };
}
