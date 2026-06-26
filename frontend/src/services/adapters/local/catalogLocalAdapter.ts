import type { CatalogAdapter } from "../../catalogService";
import { categories } from "../../../data/categories";
import { products } from "../../../data/products";

export const catalogLocalAdapter: CatalogAdapter = {
  async listCategories() {
    return categories;
  },
  async listProducts() {
    return products;
  },
  async getProductById(productId) {
    return products.find((product) => product.id === productId) ?? null;
  },
};
