import type { CartLine, FulfillmentMethod, SelectedOption, TotalsSnapshot } from "../contracts/order";

export interface CartView {
  cartToken: string | null;
  fulfillment: FulfillmentMethod;
  lines: CartLine[];
  totals: TotalsSnapshot;
  promoCode: string | null;
}

export interface AddCartLineInput {
  menuItemId: string;
  quantity: number;
  note?: string;
  selectedModifiers: SelectedOption[];
}

export interface CartFulfillmentInput {
  fulfillmentType: FulfillmentMethod;
  customerContext: Record<string, unknown>;
  fulfillmentContext: Record<string, unknown>;
}

export interface CartAdapter {
  getCart(): Promise<CartView>;
  addLine(input: AddCartLineInput): Promise<CartView>;
  updateLine(lineId: string, quantity: number): Promise<CartView>;
  removeLine(lineId: string): Promise<CartView>;
  updateFulfillment(input: CartFulfillmentInput): Promise<CartView>;
  updatePromo(promoCode: string | null): Promise<CartView>;
  quote(input: Partial<CartFulfillmentInput> & { promoCode?: string | null }): Promise<CartView>;
}

export type CartService = CartAdapter;

export function createCartService(adapter: CartAdapter): CartService {
  return {
    getCart: () => adapter.getCart(),
    addLine: (input) => adapter.addLine(input),
    updateLine: (lineId, quantity) => adapter.updateLine(lineId, quantity),
    removeLine: (lineId) => adapter.removeLine(lineId),
    updateFulfillment: (input) => adapter.updateFulfillment(input),
    updatePromo: (promoCode) => adapter.updatePromo(promoCode),
    quote: (input) => adapter.quote(input),
  };
}
