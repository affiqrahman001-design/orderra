import type { CartLine, FulfillmentMethod, TotalsSnapshot } from "../contracts/order";
import type { PromoApplication } from "../contracts/promo";

export const DELIVERY_FEE = 6;
export const SERVICE_FEE = 2;

export function getCartItemCount(lines: CartLine[]): number {
  return lines.reduce((sum, line) => sum + line.quantity, 0);
}

export function getSubtotal(lines: CartLine[]): number {
  return lines.reduce((sum, line) => sum + line.unitPrice * line.quantity, 0);
}

export function getEmptyTotals(): TotalsSnapshot {
  return {
    subtotal: 0,
    deliveryFee: 0,
    serviceFee: 0,
    discount: 0,
    total: 0,
  };
}

export function getTotals(
  lines: CartLine[],
  fulfillment: FulfillmentMethod,
  appliedPromo?: PromoApplication | null,
): TotalsSnapshot {
  const subtotal = getSubtotal(lines);
  if (subtotal <= 0 || getCartItemCount(lines) <= 0) return getEmptyTotals();

  const discount = Math.min(appliedPromo?.amount ?? 0, subtotal);
  const deliveryFee = fulfillment === "delivery" ? DELIVERY_FEE : 0;
  const serviceFee = SERVICE_FEE;
  const total = Math.max(0, subtotal + deliveryFee + serviceFee - discount);

  return {
    subtotal,
    deliveryFee,
    serviceFee,
    discount,
    total,
  };
}
