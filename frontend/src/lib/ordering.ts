import type {
  CheckoutForm,
  CartLine,
  FulfillmentMethod,
  OrderStatusCode,
  OrderStatusEvent,
  PaymentMethodCode,
  SelectedOption,
  SplitBillPlan,
  SplitBillShare,
  SplitParticipant,
  TableQrSession,
  TableSessionAction,
} from "../contracts/order";
import type { PaymentState } from "../contracts/payment";
import type { Product, ProductOptionGroup } from "../contracts/product";
import { getOrderStatusFromPaymentState } from "./payment";

export interface ProductSelectionState {
  single: Record<string, string>;
  multiple: Record<string, string[]>;
  text: Record<string, string>;
}

export function buildInitialSelectionState(product: Product): ProductSelectionState {
  const single: Record<string, string> = {};
  const multiple: Record<string, string[]> = {};
  const text: Record<string, string> = {};

  product.optionGroups.forEach((group) => {
    if (group.selectionMode === "single" && group.required && group.options?.length) {
      single[group.id] = group.options[0].id;
    }

    if (group.selectionMode === "multiple") {
      multiple[group.id] = [];
    }

    if (group.selectionMode === "text") {
      text[group.id] = "";
    }
  });

  return { single, multiple, text };
}

function resolveSingleGroup(
  group: ProductOptionGroup,
  selectedOptionId: string | undefined,
): SelectedOption[] {
  if (!selectedOptionId || !group.options) {
    return [];
  }

  const option = group.options.find((entry) => entry.id === selectedOptionId);

  if (!option) {
    return [];
  }

  return [
    {
      groupId: group.id,
      groupLabel: group.label,
      optionId: option.id,
      label: option.label,
      priceDelta: option.priceDelta,
    },
  ];
}

function resolveMultipleGroup(
  group: ProductOptionGroup,
  selectedOptionIds: string[] | undefined,
): SelectedOption[] {
  if (!selectedOptionIds?.length || !group.options) {
    return [];
  }

  return selectedOptionIds
    .map((selectedId) => group.options?.find((entry) => entry.id === selectedId))
    .filter((entry): entry is NonNullable<typeof entry> => Boolean(entry))
    .map((entry) => ({
      groupId: group.id,
      groupLabel: group.label,
      optionId: entry.id,
      label: entry.label,
      priceDelta: entry.priceDelta,
    }));
}

export function flattenSelections(
  product: Product,
  state: ProductSelectionState,
): SelectedOption[] {
  return product.optionGroups.flatMap((group) => {
    if (group.selectionMode === "single") {
      return resolveSingleGroup(group, state.single[group.id]);
    }

    if (group.selectionMode === "multiple") {
      return resolveMultipleGroup(group, state.multiple[group.id]);
    }

    return [];
  });
}

export function getSelectionPriceDelta(selections: SelectedOption[]): number {
  return selections.reduce((sum, selection) => sum + selection.priceDelta, 0);
}

export function validateRequiredSelections(
  product: Product,
  state: ProductSelectionState,
): string[] {
  const errors: string[] = [];

  product.optionGroups.forEach((group) => {
    if (!group.required) {
      return;
    }

    if (group.selectionMode === "single" && !state.single[group.id]) {
      errors.push(`Please choose ${group.label.toLowerCase()}.`);
    }

    if (group.selectionMode === "multiple" && !(state.multiple[group.id]?.length ?? 0)) {
      errors.push(`Please choose at least one option for ${group.label.toLowerCase()}.`);
    }

    if (
      group.selectionMode === "text" &&
      !(state.text[group.id] ?? "").trim()
    ) {
      errors.push(`Please complete ${group.label.toLowerCase()}.`);
    }
  });

  return errors;
}

export type CheckoutFieldKey =
  | "name"
  | "email"
  | "phone"
  | "address"
  | "city"
  | "postalCode"
  | "tableReference";

export type CheckoutErrors = Partial<Record<CheckoutFieldKey, string>>;

function createSessionAction(
  type: TableSessionAction["type"],
  note?: string,
  occurredAt = new Date().toISOString(),
): TableSessionAction {
  return {
    type,
    occurredAt,
    note,
  };
}

export function getFulfillmentStatusFlow(
  fulfillment: FulfillmentMethod,
  paymentMethod: PaymentMethodCode,
  paymentState: PaymentState = paymentMethod === "cash" ? "succeeded" : "authorized",
): OrderStatusCode[] {
  const baseFlow: OrderStatusCode[] =
    paymentMethod === "cash" && fulfillment === "dine_in"
      ? ["placed", "confirmed", "preparing", "ready", "served", "bill_requested", "paid_at_table", "completed"]
      : paymentMethod === "cash" && fulfillment === "pickup"
        ? ["placed", "confirmed", "preparing", "ready_for_pickup", "picked_up_by_customer", "completed"]
        : fulfillment === "delivery"
          ? [
              "placed",
              "confirmed",
              "preparing",
              "ready",
              "awaiting_rider",
              "rider_assigned",
              "picked_up",
              "near_customer",
              "delivered",
              "completed",
            ]
          : fulfillment === "pickup"
            ? ["placed", "confirmed", "preparing", "ready_for_pickup", "picked_up_by_customer", "completed"]
            : ["placed", "confirmed", "preparing", "ready", "served", "completed"];

  const initialStatus = getOrderStatusFromPaymentState(paymentState);
  return initialStatus === "placed" ? baseFlow : [initialStatus, ...baseFlow];
}

export function getInitialOrderStatus(
  fulfillment: FulfillmentMethod,
  paymentMethod: PaymentMethodCode,
  paymentState: PaymentState = paymentMethod === "cash" ? "succeeded" : "authorized",
): OrderStatusCode {
  return getFulfillmentStatusFlow(fulfillment, paymentMethod, paymentState)[0] ?? "placed";
}

export function getFulfillmentEstimate(fulfillment: FulfillmentMethod): number {
  if (fulfillment === "pickup") {
    return 18;
  }

  if (fulfillment === "dine_in") {
    return 14;
  }

  return 32;
}

export function buildStatusHistory(
  currentStatus: OrderStatusCode,
  placedAt: string,
): OrderStatusEvent[] {
  return [
    {
      status: currentStatus,
      occurredAt: placedAt,
    },
  ];
}

export function createDefaultSplitParticipants(primaryPayerName = ""): SplitParticipant[] {
  return [
    {
      id: "guest-1",
      name: primaryPayerName || "Guest 1",
      itemLineIds: [],
    },
  ];
}

export function ensureSplitParticipants(
  count: number,
  current: SplitParticipant[],
  primaryPayerName: string,
): SplitParticipant[] {
  const safeCount = Math.max(1, count);

  return Array.from({ length: safeCount }, (_, index) => {
    const existing = current[index];
    return {
      id: existing?.id ?? `guest-${index + 1}`,
      name:
        existing?.name ||
        (index === 0 && primaryPayerName.trim() ? primaryPayerName.trim() : `Guest ${index + 1}`),
      itemLineIds: existing?.itemLineIds ?? [],
    };
  });
}

export function buildSplitBillPlan(checkout: CheckoutForm): SplitBillPlan {
  return {
    mode: checkout.splitBillMode,
    participantCount: checkout.splitParticipantCount,
    participants: checkout.splitParticipants,
    primaryPayerName: checkout.primaryPayerName.trim() || checkout.name.trim(),
  };
}

export function getPrimaryPayerName(checkout: CheckoutForm): string {
  return checkout.primaryPayerName.trim() || checkout.name.trim();
}

export function buildSplitShares(
  lines: CartLine[],
  total: number,
  plan: SplitBillPlan,
): SplitBillShare[] {
  if (plan.mode === "none") {
    return [
      {
        participantId: plan.participants[0]?.id ?? "guest-1",
        participantName: plan.primaryPayerName || plan.participants[0]?.name || "Primary payer",
        itemLineIds: lines.map((line) => line.id),
        amount: total,
      },
    ];
  }

  if (plan.mode === "equal") {
    const participantCount = Math.max(1, plan.participants.length);
    const baseShare = Math.floor(total / participantCount);
    const remainder = total - baseShare * participantCount;

    return plan.participants.map((participant, index) => ({
      participantId: participant.id,
      participantName: participant.name,
      itemLineIds: [],
      amount: baseShare + (index === 0 ? remainder : 0),
    }));
  }

  const lineTotals = new Map(lines.map((line) => [line.id, line.unitPrice * line.quantity]));
  const shares = plan.participants.map((participant) => ({
    participantId: participant.id,
    participantName: participant.name,
    itemLineIds: participant.itemLineIds,
    amount: participant.itemLineIds.reduce((sum, lineId) => sum + (lineTotals.get(lineId) ?? 0), 0),
  }));

  const assigned = shares.reduce((sum, share) => sum + share.amount, 0);
  const remainder = Math.max(0, total - assigned);

  if (remainder > 0 && shares.length > 0) {
    shares[0] = {
      ...shares[0],
      amount: shares[0].amount + remainder,
      itemLineIds:
        shares[0].itemLineIds.length > 0
          ? shares[0].itemLineIds
          : lines.map((line) => line.id),
    };
  }

  return shares;
}

export function createTableSession(tableReference: string, orderId: string, occurredAt: string): TableQrSession {
  const sessionId = `tbl-${tableReference.toLowerCase().replace(/[^a-z0-9]+/g, "-")}-${Date.now()}`;
  const qrSessionCode = `QR-${Math.random().toString(36).slice(2, 8).toUpperCase()}`;

  return {
    sessionId,
    tableReference,
    qrSessionCode,
    joinUrl: `/demo/qr/${qrSessionCode}`,
    status: "open",
    activeOrderIds: [orderId],
    actionHistory: [createSessionAction("session_created", `Session opened for ${tableReference}.`, occurredAt)],
  };
}

export function appendTableSessionOrder(
  session: TableQrSession,
  orderId: string,
  occurredAt: string,
): TableQrSession {
  return {
    ...session,
    activeOrderIds: [...session.activeOrderIds, orderId],
    actionHistory: [
      ...session.actionHistory,
      createSessionAction("items_added", "Additional items added to the same table session.", occurredAt),
    ],
  };
}

export function markWaiterCalled(session: TableQrSession): TableQrSession {
  return {
    ...session,
    actionHistory: [
      ...session.actionHistory,
      createSessionAction("waiter_called", "Waiter call placeholder triggered in demo mode."),
    ],
  };
}

export function markBillRequested(session: TableQrSession): TableQrSession {
  return {
    ...session,
    status: "bill_requested",
    actionHistory: [
      ...session.actionHistory,
      createSessionAction("bill_requested", "Bill request placeholder triggered in demo mode."),
    ],
  };
}

export function markPayAtTableReady(session: TableQrSession): TableQrSession {
  return {
    ...session,
    status: "payment_ready",
    actionHistory: [
      ...session.actionHistory,
      createSessionAction("pay_at_table_ready", "Pay-at-table placeholder state is ready in demo mode."),
    ],
  };
}

export function formatOrderStatusLabel(status: OrderStatusCode): string {
  return status
    .split("_")
    .map((part) => part.charAt(0).toUpperCase() + part.slice(1))
    .join(" ");
}

export function formatFulfillmentLabel(fulfillment: FulfillmentMethod): string {
  return fulfillment === "dine_in"
    ? "Dine in"
    : fulfillment.charAt(0).toUpperCase() + fulfillment.slice(1);
}

export function getFulfillmentSummaryLabel(fulfillment: FulfillmentMethod): string {
  if (fulfillment === "delivery") {
    return "Delivery";
  }

  if (fulfillment === "pickup") {
    return "Pickup";
  }

  return "Dine in";
}

export function validateCheckoutForm(checkout: CheckoutForm): CheckoutErrors {
  const errors: CheckoutErrors = {};

  if (!checkout.name.trim()) {
    errors.name = "Add the guest name for this order.";
  }

  if (!checkout.email.trim()) {
    errors.email = "Add an email for the order reference.";
  }

  if (!checkout.phone.trim()) {
    errors.phone = "Add a phone number for timing updates.";
  }

  if (checkout.fulfillment === "delivery") {
    if (!checkout.address.trim()) {
      errors.address = "Add the delivery address.";
    }

    if (!checkout.city.trim()) {
      errors.city = "Add the delivery city.";
    }

    if (!checkout.postalCode.trim()) {
      errors.postalCode = "Add the delivery ZIP code.";
    }
  }

  if (checkout.fulfillment === "dine_in" && !checkout.tableReference.trim()) {
    errors.tableReference = "Add a table or seat reference for dine-in handoff.";
  }

  return errors;
}
