export type ProductFlow = "none" | "simple" | "full" | "combo";

export interface ProductOption {
  id: string;
  label: string;
  priceDelta: number;
}

export interface ProductOptionGroup {
  id: string;
  label: string;
  helperText?: string;
  selectionMode: "single" | "multiple" | "text";
  required?: boolean;
  placeholder?: string;
  options?: ProductOption[];
}

export interface Product {
  id: string;
  slug: string;
  name: string;
  shortName: string;
  description: string;
  price: number;
  image: string;
  imageAlt: string;
  categoryId: string;
  badge?: string;
  flow: ProductFlow;
  available: boolean;
  featured: boolean;
  prepNote?: string;
  optionGroups: ProductOptionGroup[];
}
