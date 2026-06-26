import type { OrderSummary, OrderStatusCode } from "../contracts/order";
import type {
  OperationalLogEntry,
  RefundDecisionShape,
  RefundRecord,
  RiderSimulationState,
  SupportCompensationType,
  WebhookSimulationEvent,
} from "../contracts/operations";

function createId(prefix: string): string {
  return `${prefix}-${Math.random().toString(36).slice(2, 8)}`;
}

function createLog(kind: OperationalLogEntry["kind"], message: string): OperationalLogEntry {
  return {
    id: createId(kind),
    kind,
    message,
    createdAt: new Date().toISOString(),
  };
}

export function getDefaultRefundDecision(type: SupportCompensationType, order: OrderSummary): RefundDecisionShape {
  const defaults: Record<SupportCompensationType, RefundDecisionShape> = {
    missing_item: {
      type,
      refundState: "partially_refunded",
      amount: Math.max(2, Math.round(order.totals.total * 0.25)),
      reason: "Missing item reported in demo mode.",
    },
    wrong_item: {
      type,
      refundState: "partially_refunded",
      amount: Math.max(2, Math.round(order.totals.total * 0.3)),
      reason: "Wrong item compensation placeholder in demo mode.",
    },
    late_delivery: {
      type,
      refundState: "partially_refunded",
      amount: Math.max(2, Math.round(order.totals.deliveryFee + order.totals.serviceFee)),
      reason: "Late delivery compensation placeholder in demo mode.",
    },
    partial_refund: {
      type,
      refundState: "refund_pending",
      amount: Math.max(2, Math.round(order.totals.total * 0.2)),
      reason: "Partial refund review placeholder in demo mode.",
    },
    store_credit: {
      type,
      refundState: "refund_pending",
      amount: Math.max(2, Math.round(order.totals.total * 0.15)),
      reason: "Store credit placeholder captured as refund review in demo mode.",
    },
  };

  return defaults[type];
}

function createRefundRecord(decision: RefundDecisionShape): RefundRecord {
  return {
    state: decision.refundState,
    amount: decision.amount,
    type: decision.type,
    reason: decision.reason,
    decidedAt: new Date().toISOString(),
  };
}

function createWebhookEvent(type: WebhookSimulationEvent["type"], payloadSummary: string): WebhookSimulationEvent {
  return {
    id: createId("wh"),
    type,
    createdAt: new Date().toISOString(),
    payloadSummary,
  };
}

function nextRiderStatus(
  status: RiderSimulationState["currentStatus"],
): RiderSimulationState["currentStatus"] {
  const flow: RiderSimulationState["currentStatus"][] = [
    "awaiting_rider",
    "rider_assigned",
    "picked_up",
    "near_customer",
    "delivered",
  ];

  const index = flow.indexOf(status);
  return flow[Math.min(index + 1, flow.length - 1)];
}

function getRiderEta(status: RiderSimulationState["currentStatus"]): number {
  if (status === "awaiting_rider") return 18;
  if (status === "rider_assigned") return 14;
  if (status === "picked_up") return 9;
  if (status === "near_customer") return 3;
  return 0;
}

export function createInitialRiderSimulation(): RiderSimulationState {
  return {
    currentStatus: "awaiting_rider",
    riderName: undefined,
    etaMinutes: 18,
    timeline: [
      {
        status: "awaiting_rider",
        occurredAt: new Date().toISOString(),
        etaMinutes: 18,
        note: "Delivery handoff is waiting for rider simulation in demo mode.",
      },
    ],
  };
}

function syncStatusFlow(statusFlow: OrderStatusCode[], status: OrderStatusCode): OrderStatusCode[] {
  return statusFlow.includes(status) ? statusFlow : [status, ...statusFlow];
}

export function applyRefundDecision(order: OrderSummary, decision: RefundDecisionShape): OrderSummary {
  const record = createRefundRecord(decision);
  const nextStatus = decision.refundState;

  return {
    ...order,
    status: nextStatus,
    statusFlow: syncStatusFlow(order.statusFlow, nextStatus),
    statusHistory: [
      ...order.statusHistory,
      {
        status: nextStatus,
        occurredAt: record.decidedAt,
        note: decision.reason,
      },
    ],
    refunds: [...(order.refunds ?? []), record],
    webhookEvents: [
      ...(order.webhookEvents ?? []),
      createWebhookEvent("refund.updated", `${decision.type} -> ${decision.refundState}`),
    ],
    operationalLogs: [
      ...(order.operationalLogs ?? []),
      createLog("refund", `${decision.type} compensation logged as ${decision.refundState}.`),
    ],
  };
}

export function applyWebhookSimulation(
  order: OrderSummary,
  type: WebhookSimulationEvent["type"],
  payloadSummary: string,
): OrderSummary {
  const occurredAt = new Date().toISOString();
  const statusByWebhook: Partial<Record<WebhookSimulationEvent["type"], OrderStatusCode>> = {
    "order.confirmed": "confirmed",
    "rider.assigned": "rider_assigned",
    "order.delivered": "delivered",
  };
  const nextStatus = statusByWebhook[type];

  return {
    ...order,
    status: nextStatus ?? order.status,
    statusFlow: nextStatus ? syncStatusFlow(order.statusFlow, nextStatus) : order.statusFlow,
    statusHistory: nextStatus
      ? [
          ...order.statusHistory,
          {
            status: nextStatus,
            occurredAt,
            note: payloadSummary,
          },
        ]
      : order.statusHistory,
    webhookEvents: [...(order.webhookEvents ?? []), createWebhookEvent(type, payloadSummary)],
    operationalLogs: [
      ...(order.operationalLogs ?? []),
      createLog("webhook", `Webhook ${type} simulated.`),
    ],
  };
}

export function advanceRiderSimulation(order: OrderSummary): OrderSummary {
  const current = order.riderSimulation ?? createInitialRiderSimulation();
  const nextStatus = nextRiderStatus(current.currentStatus);
  const occurredAt = new Date().toISOString();
  const riderName = current.riderName ?? "Jordan P.";
  const etaMinutes = getRiderEta(nextStatus);

  const nextSimulation: RiderSimulationState = {
    currentStatus: nextStatus,
    riderName,
    etaMinutes,
    timeline: [
      ...current.timeline,
      {
        status: nextStatus,
        occurredAt,
        etaMinutes,
        note:
          nextStatus === "rider_assigned"
            ? "Rider placeholder assigned in demo mode."
            : nextStatus === "picked_up"
              ? "Order handed to the rider placeholder."
              : nextStatus === "near_customer"
                ? "Rider placeholder is near the customer."
                : "Delivery completed in demo mode."
      },
    ],
  };

  const webhookType: WebhookSimulationEvent["type"] =
    nextStatus === "rider_assigned"
      ? "rider.assigned"
      : nextStatus === "delivered"
        ? "order.delivered"
        : "rider.location_updated";

  return {
    ...order,
    status: nextStatus,
    statusFlow: syncStatusFlow(order.statusFlow, nextStatus),
    statusHistory: [
      ...order.statusHistory,
      {
        status: nextStatus,
        occurredAt,
      },
    ],
    riderSimulation: nextSimulation,
    webhookEvents: [
      ...(order.webhookEvents ?? []),
      createWebhookEvent(webhookType, `${nextStatus} ETA ${etaMinutes} minutes`),
    ],
    operationalLogs: [
      ...(order.operationalLogs ?? []),
      createLog("rider", `Rider simulation moved to ${nextStatus}.`),
    ],
  };
}
