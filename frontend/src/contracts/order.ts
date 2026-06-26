import type { ProductFlow } from "./product";
import type {
  OperationalLogEntry,
  RefundRecord,
  RiderSimulationState,
  WebhookSimulationEvent,
} from "./operations";
import type { PaymentSimulationOutcome, PaymentSimulationResult, PaymentState } from "./payment";

export type FulfillmentMethod = "delivery" | "pickup" | "dine_in";

export type PaymentMethodCode =
  | "card"
  | "apple_pay"
  | "google_pay"
  | "ach"
  | "cash"
  | "paypal"
  | "fpx"
  | "duitnow_qr";

export type OrderStatusCode =
  | "cart_draft"
  | "pending_payment"
  | "payment_authorized"
  | "placed"
  | "confirmed"
  | "preparing"
  | "ready"
  | "awaiting_rider"
  | "rider_assigned"
  | "picked_up"
  | "near_customer"
  | "delivered"
  | "ready_for_pickup"
  | "picked_up_by_customer"
  | "served"
  | "bill_requested"
  | "paid_at_table"
  | "completed"
  | "cancelled"
  | "refund_pending"
  | "refunded"
  | "partially_refunded";

export interface OrderStatusEvent {
  status: OrderStatusCode;
  occurredAt: string;
  note?: string;
}

export interface SelectedOption {
  groupId: string;
  groupLabel: string;
  optionId: string;
  label: string;
  priceDelta: number;
}

export interface CartLine {
  id: string;
  productId: string;
  name: string;
  image: string;
  quantity: number;
  basePrice: number;
  unitPrice: number;
  flow: ProductFlow;
  selections: SelectedOption[];
  note?: string;
}

export type SplitBillMode = "none" | "equal" | "by_item";

export interface SplitParticipant {
  id: string;
  name: string;
  itemLineIds: string[];
}

export interface SplitBillPlan {
  mode: SplitBillMode;
  participantCount: number;
  participants: SplitParticipant[];
  primaryPayerName: string;
}

export interface SplitBillShare {
  participantId: string;
  participantName: string;
  itemLineIds: string[];
  amount: number;
}

export interface TableSessionAction {
  type: "session_created" | "items_added" | "waiter_called" | "bill_requested" | "pay_at_table_ready";
  occurredAt: string;
  note?: string;
}

export interface TableQrSession {
  sessionId: string;
  tableReference: string;
  qrSessionCode: string;
  joinUrl: string;
  status: "open" | "bill_requested" | "payment_ready" | "closed";
  activeOrderIds: string[];
  actionHistory: TableSessionAction[];
}

export interface CheckoutForm {
  name: string;
  email: string;
  phone: string;
  fulfillment: FulfillmentMethod;
  address: string;
  city: string;
  postalCode: string;
  tableReference: string;
  paymentMethod: PaymentMethodCode;
  paymentSimulationOutcome: PaymentSimulationOutcome;
  deliveryWindow: string;
  contactless: boolean;
  splitBillMode: SplitBillMode;
  splitParticipantCount: number;
  splitParticipants: SplitParticipant[];
  primaryPayerName: string;
}

export interface TotalsSnapshot {
  subtotal: number;
  deliveryFee: number;
  serviceFee: number;
  discount: number;
  total: number;
}

export interface CreateOrderInput {
  cartLines: CartLine[];
  checkout: CheckoutForm;
  promoCode?: string;
  totals: TotalsSnapshot;
  payment?: PaymentSimulationResult;
  existingTableSessionId?: string;
}

export interface OrderSummary {
  orderId: string;
  publicCode: string;
  status: OrderStatusCode;
  estimatedReadyInMinutes: number;
  items: CartLine[];
  totals: TotalsSnapshot;
  placedAt: string;
  fulfillment: FulfillmentMethod;
  paymentMethod?: PaymentMethodCode;
  paymentState?: PaymentState;
  payment?: PaymentSimulationResult;
  customerName: string;
  statusHistory: OrderStatusEvent[];
  statusFlow: OrderStatusCode[];
  tableSession?: TableQrSession;
  splitBill?: SplitBillPlan;
  splitShares?: SplitBillShare[];
  canAddMoreItems?: boolean;
  refunds?: RefundRecord[];
  webhookEvents?: WebhookSimulationEvent[];
  deliveryAssignmentId?: string;
  riderSimulation?: RiderSimulationState;
  operationalLogs?: OperationalLogEntry[];
}
