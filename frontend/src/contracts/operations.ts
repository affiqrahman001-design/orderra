import type { OrderStatusCode } from "./order";

export type RefundState = "refund_pending" | "refunded" | "partially_refunded";

export type SupportCompensationType =
  | "missing_item"
  | "wrong_item"
  | "late_delivery"
  | "partial_refund"
  | "store_credit";

export interface RefundDecisionShape {
  type: SupportCompensationType;
  refundState: RefundState;
  amount: number;
  reason: string;
}

export interface RefundRecord {
  state: RefundState;
  amount: number;
  type: SupportCompensationType;
  reason: string;
  decidedAt: string;
}

export interface WebhookSimulationEvent {
  id: string;
  type:
    | "payment.updated"
    | "order.confirmed"
    | "rider.assigned"
    | "rider.location_updated"
    | "order.delivered"
    | "refund.updated";
  createdAt: string;
  payloadSummary: string;
}

export interface RiderTimelineEvent {
  status: Extract<
    OrderStatusCode,
    "awaiting_rider" | "rider_assigned" | "picked_up" | "near_customer" | "delivered"
  >;
  occurredAt: string;
  etaMinutes: number;
  note?: string;
}

export interface RiderSimulationState {
  currentStatus: Extract<
    OrderStatusCode,
    "awaiting_rider" | "rider_assigned" | "picked_up" | "near_customer" | "delivered"
  >;
  riderName?: string;
  etaMinutes: number;
  timeline: RiderTimelineEvent[];
}

export interface OperationalLogEntry {
  id: string;
  kind: "refund" | "webhook" | "rider";
  message: string;
  createdAt: string;
}
