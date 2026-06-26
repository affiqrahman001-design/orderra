import { memo, useCallback } from 'react';
import type { Product } from '../contracts/product';
import { formatCurrency } from '../lib/currency';
import { MenuThumbnail } from './MenuThumbnail';

interface ProductCardProps {
  product: Product;
  onSelect: (product: Product) => void;
}

const flowLabelMap: Record<Product['flow'], string> = {
  none: 'Add to order',
  simple: 'Add to order',
  full: 'Add to order',
  combo: 'Add to order',
};

export const ProductCard = memo(function ProductCard({ product, onSelect }: ProductCardProps) {
  const handleSelect = useCallback(() => {
    onSelect(product);
  }, [onSelect, product]);

  return (
    <article className="product-card">
      <div className="product-card__image-wrap">
        <MenuThumbnail src={product.image} alt={product.imageAlt} className="product-card__image" />
        {product.badge ? <span className="product-card__badge">{product.badge}</span> : null}
      </div>

      <div className="product-card__body">
        <div className="product-card__heading">
          <div>
            <h3>{product.name}</h3>
            <p>{product.description}</p>
          </div>
          <strong className="product-card__price">{formatCurrency(product.price)}</strong>
        </div>

        <button
          type="button"
          className="button button--primary button--block"
          onClick={handleSelect}
          disabled={!product.available}
        >
          {product.available ? flowLabelMap[product.flow] : 'Unavailable'}
        </button>
      </div>
    </article>
  );
});
