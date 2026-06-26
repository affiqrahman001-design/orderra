import { memo } from 'react';
import type { Product } from '../contracts/product';
import { ProductCard } from './ProductCard';

interface ProductGridProps {
  products: Product[];
  onSelectProduct: (product: Product) => void;
}

export const ProductGrid = memo(function ProductGrid({
  products,
  onSelectProduct,
}: ProductGridProps) {
  return (
    <section className="product-grid">
      {products.map((product) => (
        <ProductCard key={product.id} product={product} onSelect={onSelectProduct} />
      ))}
    </section>
  );
});
