import { formatCurrency } from "../lib/currency";

interface FloatingCartButtonProps {
  itemCount: number;
  total: number;
  onClick: () => void;
}

export function FloatingCartButton({
  itemCount,
  total,
  onClick,
}: FloatingCartButtonProps) {
  if (itemCount === 0) {
    return null;
  }

  return (
    <button type="button" className="floating-cart-button" onClick={onClick}>
      <span>{itemCount} item{itemCount > 1 ? "s" : ""}</span>
      <strong>{formatCurrency(total)}</strong>
    </button>
  );
}
