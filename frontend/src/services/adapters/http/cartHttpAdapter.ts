import type {
  CartLine,
  FulfillmentMethod,
  SelectedOption,
  TotalsSnapshot,
} from '../../../contracts/order';
import { ApiError, apiRequest } from '../../../lib/api/client';
import { resolveMenuImage } from '../../../lib/menuAssets';
import { getEmptyTotals } from '../../../lib/pricing';
import type {
  AddCartLineInput,
  CartAdapter,
  CartFulfillmentInput,
  CartView,
} from '../../cartService';

const cartTokenStorageKey = 'orderra_cart_token';

export function getStoredCartToken(): string | null {
  if (typeof window === 'undefined') return null;
  return window.localStorage.getItem(cartTokenStorageKey);
}

function setStoredCartToken(token: string | null): void {
  if (typeof window === 'undefined') return;
  if (token) window.localStorage.setItem(cartTokenStorageKey, token);
  else window.localStorage.removeItem(cartTokenStorageKey);
}

export function clearStoredCartToken(): void {
  setStoredCartToken(null);
}

/** When the stored token points at a converted/invalid cart, drop it and start a fresh session. */
export function shouldResetCartTokenAfterApiError(error: unknown): boolean {
  if (!(error instanceof ApiError)) return false;
  if (error.status !== 422 && error.status !== 404) return false;
  const haystack =
    `${error.message}\n${typeof error.payload === 'string' ? error.payload : JSON.stringify(error.payload ?? '')}`.toLowerCase();
  return (
    haystack.includes('already completed checkout') ||
    haystack.includes('ditukar menjadi order') ||
    haystack.includes('start a new cart')
  );
}

type BackendMoneyTotals = {
  subtotal: number;
  discount: number;
  service_fee: number;
  delivery_fee: number;
  small_order_fee?: number;
  tax?: number;
  tip?: number;
  total: number;
};

type BackendCartLine = {
  id: number | string;
  item_name: string;
  item_slug: string;
  quantity: number;
  note?: string | null;
  image_url?: string | null;
  modifier_snapshot?: Array<{
    id?: string;
    label?: string;
    price_delta_amount?: number;
    group?: { id?: string; name?: string };
  }>;
  unit_price: number;
  line_subtotal: number;
};

type BackendCart = {
  id: string;
  cart_token: string;
  status: string;
  currency: string;
  fulfillment_type: FulfillmentMethod;
  promo_code?: string | null;
  lines: BackendCartLine[];
  totals: BackendMoneyTotals;
};

type BackendEnvelope<T> = { data: T; message?: string };

function unwrapCartPayload(envelope: BackendEnvelope<BackendCart>): BackendCart {
  const inner = envelope.data;
  if (inner && typeof inner === 'object' && inner !== null && 'data' in inner) {
    const nested = (inner as { data?: BackendCart }).data;
    if (nested && typeof nested === 'object' && 'cart_token' in nested) {
      return nested;
    }
  }
  return inner as BackendCart;
}

function mapTotals(totals: BackendMoneyTotals, lineCount: number): TotalsSnapshot {
  if (lineCount <= 0 || totals.subtotal <= 0) return getEmptyTotals();

  return {
    subtotal: totals.subtotal,
    discount: Math.min(totals.discount, totals.subtotal),
    serviceFee: totals.service_fee,
    deliveryFee: totals.delivery_fee,
    total: Math.max(0, totals.total),
  };
}

function mapSelections(modifiers: BackendCartLine['modifier_snapshot']): SelectedOption[] {
  return (modifiers ?? []).map((modifier) => ({
    groupId: modifier.group?.id ?? 'modifier-group',
    groupLabel: modifier.group?.name ?? 'Modifier',
    optionId: modifier.id ?? modifier.label ?? 'modifier-option',
    label: modifier.label ?? 'Modifier option',
    priceDelta: (modifier.price_delta_amount ?? 0) / 100,
  }));
}

function mapCart(payload: BackendEnvelope<BackendCart>): CartView {
  const cart = unwrapCartPayload(payload);
  setStoredCartToken(cart.cart_token);

  const lines: CartLine[] = cart.lines.map((line) => ({
    id: String(line.id),
    productId: line.item_slug,
    name: line.item_name,
    image: resolveMenuImage(line.item_slug, line.image_url),
    quantity: line.quantity,
    basePrice: line.unit_price,
    unitPrice: line.unit_price,
    flow: 'full',
    selections: mapSelections(line.modifier_snapshot),
    note: line.note ?? undefined,
  }));

  return {
    cartToken: cart.cart_token,
    fulfillment: cart.fulfillment_type,
    lines,
    totals: mapTotals(cart.totals, lines.length),
    promoCode: cart.promo_code ?? null,
  };
}

async function requestCart(path: string, init?: RequestInit): Promise<CartView> {
  return mapCart(
    await apiRequest<BackendEnvelope<BackendCart>>(path, {
      ...init,
      cartToken: getStoredCartToken(),
    }),
  );
}

function buildSelectedModifiers(selections: SelectedOption[]): Array<{ id: string }> {
  return selections.map((selection) => ({ id: selection.optionId }));
}

export const cartHttpAdapter: CartAdapter = {
  getCart() {
    return requestCart('/cart');
  },

  addLine(input: AddCartLineInput) {
    return requestCart('/cart/lines', {
      method: 'POST',
      body: JSON.stringify({
        menu_item_id: input.menuItemId,
        quantity: input.quantity,
        note: input.note,
        selected_modifiers: buildSelectedModifiers(input.selectedModifiers),
      }),
    });
  },

  updateLine(lineId: string, quantity: number) {
    return requestCart(`/cart/lines/${lineId}`, {
      method: 'PATCH',
      body: JSON.stringify({ quantity }),
    });
  },

  removeLine(lineId: string) {
    return requestCart(`/cart/lines/${lineId}`, { method: 'DELETE' });
  },

  updateFulfillment(input: CartFulfillmentInput) {
    return requestCart('/cart/fulfillment', {
      method: 'PATCH',
      body: JSON.stringify({
        fulfillment_type: input.fulfillmentType,
        customer_context: input.customerContext,
        fulfillment_context: input.fulfillmentContext,
      }),
    });
  },

  updatePromo(promoCode: string | null) {
    return requestCart('/cart/promo', {
      method: 'PATCH',
      body: JSON.stringify({ promo_code: promoCode }),
    });
  },

  quote(input) {
    return requestCart('/pricing/quote', {
      method: 'POST',
      body: JSON.stringify({
        fulfillment_type: input.fulfillmentType,
        customer_context: input.customerContext ?? {},
        fulfillment_context: input.fulfillmentContext ?? {},
        promo_code: input.promoCode ?? null,
      }),
    });
  },
};
