import type { OrderAdapter } from "../../orderService";
import type { CartLine, CreateOrderInput, OrderStatusCode, OrderSummary, TotalsSnapshot } from "../../../contracts/order";
import { apiRequest } from "../../../lib/api/client";
import { resolveMenuImage } from "../../../lib/menuAssets";
import { buildStatusHistory, getFulfillmentEstimate, getFulfillmentStatusFlow } from "../../../lib/ordering";
import { createInitialRiderSimulation } from "../../../lib/operations";
import { getStoredCartToken } from "./cartHttpAdapter";

type BackendMoneyTotals = {
  subtotal: number;
  discount: number;
  service_fee: number;
  delivery_fee: number;
  total: number;
};

type BackendOrderItem = {
  id: string | number;
  item_name?: string;
  name?: string;
  item_slug?: string;
  quantity: number;
  unit_price: number;
  line_total?: number;
  image_url?: string | null;
  item_snapshot?: { image_url?: string | null } | Record<string, unknown>;
};

type BackendOrder = {
  id: string;
  public_id?: string;
  public_code?: string;
  order_code?: string;
  status: OrderStatusCode;
  fulfillment_type?: OrderSummary["fulfillment"];
  placed_at?: string;
  created_at?: string;
  customer_name?: string;
  customer_context?: { name?: string; email?: string; phone?: string };
  payment_method?: OrderSummary["paymentMethod"];
  payment_state?: OrderSummary["paymentState"];
  totals?: BackendMoneyTotals;
  items?: BackendOrderItem[];
  timeline?: Array<{ status: OrderStatusCode; occurred_at?: string; occurredAt?: string; note?: string }>;
  status_history?: Array<{ to_status?: OrderStatusCode; occurred_at?: string; created_at?: string; note?: string }>;
  delivery_assignment_id?: string;
};

type BackendEnvelope<T> = { data: T; message?: string };

function mapTotals(totals: BackendMoneyTotals | undefined, fallback: TotalsSnapshot): TotalsSnapshot {
  if (!totals) return fallback;
  return {
    subtotal: totals.subtotal,
    discount: totals.discount,
    serviceFee: totals.service_fee,
    deliveryFee: totals.delivery_fee,
    total: totals.total,
  };
}

function mapItems(items: BackendOrderItem[] | undefined, fallback: CartLine[]): CartLine[] {
  if (!items?.length) return fallback;
  return items.map((item) => ({
    id: String(item.id),
    productId: item.item_slug ?? String(item.id),
    name: item.item_name ?? item.name ?? "ORDERra item",
    image: resolveMenuImage(
      item.item_slug ?? String(item.id),
      item.image_url ?? (item.item_snapshot && "image_url" in item.item_snapshot ? String(item.item_snapshot.image_url ?? "") : null),
    ),
    quantity: item.quantity,
    basePrice: item.unit_price,
    unitPrice: item.unit_price,
    flow: "full",
    selections: [],
  }));
}

function mapTimelineFromBackend(payload: BackendOrder, placedAt: string): OrderSummary["statusHistory"] {
  if (payload.timeline?.length) {
    return payload.timeline.map((entry) => ({
      status: entry.status,
      occurredAt: entry.occurred_at ?? entry.occurredAt ?? placedAt,
      note: entry.note,
    }));
  }
  if (payload.status_history?.length) {
    return payload.status_history.map((entry) => ({
      status: entry.to_status ?? "placed",
      occurredAt: entry.created_at ?? entry.occurred_at ?? placedAt,
      note: entry.note,
    }));
  }
  return buildStatusHistory(payload.status, placedAt);
}

function mapOrder(payload: BackendOrder, input?: CreateOrderInput): OrderSummary {
  const fulfillment = payload.fulfillment_type ?? input?.checkout.fulfillment ?? "delivery";
  const placedAt = payload.placed_at ?? payload.created_at ?? new Date().toISOString();
  const paymentState = payload.payment_state ?? input?.payment?.state ?? "authorized";
  const paymentMethod = payload.payment_method ?? input?.checkout.paymentMethod;
  const statusFlow = getFulfillmentStatusFlow(fulfillment, paymentMethod ?? "card", paymentState);
  const customerName =
    payload.customer_name ??
    payload.customer_context?.name ??
    input?.checkout.name ??
    "Guest";

  return {
    orderId: payload.public_id ?? payload.id,
    publicCode: payload.order_code ?? payload.public_code ?? payload.public_id ?? payload.id,
    status: payload.status,
    estimatedReadyInMinutes: getFulfillmentEstimate(fulfillment),
    items: mapItems(payload.items, input?.cartLines ?? []),
    totals: mapTotals(payload.totals, input?.totals ?? { subtotal: 0, discount: 0, deliveryFee: 0, serviceFee: 0, total: 0 }),
    placedAt,
    fulfillment,
    paymentMethod,
    paymentState,
    payment: input?.payment,
    customerName,
    statusHistory: mapTimelineFromBackend(payload, placedAt),
    statusFlow,
    deliveryAssignmentId: payload.delivery_assignment_id,
    riderSimulation: fulfillment === "delivery" ? createInitialRiderSimulation() : undefined,
    refunds: [],
    webhookEvents: [],
    operationalLogs: [],
  };
}

function unwrapOrderPayload(envelope: BackendEnvelope<BackendOrder>): BackendOrder {
  let inner = envelope.data;
  if (inner && typeof inner === "object" && inner !== null && "data" in inner) {
    const nested = (inner as { data?: BackendOrder }).data;
    if (nested && typeof nested === "object") inner = nested;
  }
  return inner as BackendOrder;
}

export const orderHttpAdapter: OrderAdapter = {
  async createOrder(input: CreateOrderInput) {
    const cartToken = getStoredCartToken();
    const paymentIntentId = input.payment?.paymentIntentId;

    if (!cartToken) throw new Error("Cart token is missing. Add an item before checkout.");
    if (!paymentIntentId) throw new Error("Payment intent is missing. Simulate payment before placing order.");

    const response = await apiRequest<BackendEnvelope<BackendOrder>>("/checkout", {
      method: "POST",
      cartToken,
      body: JSON.stringify({
        cart_token: cartToken,
        payment_intent_id: paymentIntentId,
        customer: {
          name: input.checkout.name,
          email: input.checkout.email,
          phone: input.checkout.phone,
        },
      }),
    });

    return mapOrder(unwrapOrderPayload(response), input);
  },

  async getOrderById(orderId) {
    const response = await apiRequest<BackendEnvelope<BackendOrder>>(`/orders/${orderId}`);
    return mapOrder(unwrapOrderPayload(response));
  },
};
