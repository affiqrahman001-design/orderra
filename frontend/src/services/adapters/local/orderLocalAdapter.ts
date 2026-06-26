import type { OrderAdapter } from "../../orderService";
import type { CreateOrderInput, OrderSummary } from "../../../contracts/order";
import {
  appendTableSessionOrder,
  buildSplitBillPlan,
  buildSplitShares,
  buildStatusHistory,
  createTableSession,
  getFulfillmentEstimate,
  getFulfillmentStatusFlow,
  getInitialOrderStatus,
} from "../../../lib/ordering";
import { createInitialRiderSimulation } from "../../../lib/operations";

export const inMemoryOrders = new Map<string, OrderSummary>();
const tableSessionsById = new Map<string, OrderSummary["tableSession"]>();

function createPublicCode(): string {
  return Math.random().toString(36).slice(2, 8).toUpperCase();
}

function createOrderId(): string {
  if ("randomUUID" in crypto) {
    return crypto.randomUUID();
  }

  return `ord-${Date.now()}`;
}

export const orderLocalAdapter: OrderAdapter = {
  async createOrder(input: CreateOrderInput) {
    const orderId = createOrderId();
    const placedAt = new Date().toISOString();
    const paymentState = input.payment?.state ?? (input.checkout.paymentMethod === "cash" ? "succeeded" : "authorized");
    const status = getInitialOrderStatus(input.checkout.fulfillment, input.checkout.paymentMethod, paymentState);
    const statusFlow = getFulfillmentStatusFlow(
      input.checkout.fulfillment,
      input.checkout.paymentMethod,
      paymentState,
    );
    const splitBill = buildSplitBillPlan(input.checkout);
    const splitShares = buildSplitShares(input.cartLines, input.totals.total, splitBill);
    let tableSession =
      input.checkout.fulfillment === "dine_in" && input.existingTableSessionId
        ? tableSessionsById.get(input.existingTableSessionId) ?? null
        : null;

    if (input.checkout.fulfillment === "dine_in" && input.checkout.tableReference.trim()) {
      tableSession = tableSession
        ? appendTableSessionOrder(tableSession, orderId, placedAt)
        : createTableSession(input.checkout.tableReference.trim(), orderId, placedAt);

      tableSessionsById.set(tableSession.sessionId, tableSession);
    }

    const order: OrderSummary = {
      orderId,
      publicCode: createPublicCode(),
      status,
      estimatedReadyInMinutes: getFulfillmentEstimate(input.checkout.fulfillment),
      items: input.cartLines,
      totals: input.totals,
      placedAt,
      fulfillment: input.checkout.fulfillment,
      paymentMethod: input.checkout.paymentMethod,
      paymentState,
      payment: input.payment,
      customerName: input.checkout.name,
      statusHistory: buildStatusHistory(status, placedAt),
      statusFlow,
      tableSession: tableSession ?? undefined,
      splitBill,
      splitShares,
      canAddMoreItems: input.checkout.fulfillment === "dine_in" && Boolean(tableSession),
      riderSimulation: input.checkout.fulfillment === "delivery" ? createInitialRiderSimulation() : undefined,
      refunds: [],
      webhookEvents: [],
      operationalLogs: [],
    };

    inMemoryOrders.set(orderId, order);

    return order;
  },
  async getOrderById(orderId) {
    return inMemoryOrders.get(orderId) ?? null;
  },
};
