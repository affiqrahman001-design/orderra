import type { OperationsAdapter } from "../../operationsService";
import type { OrderSummary } from "../../../contracts/order";
import type { RefundDecisionShape, WebhookSimulationEvent } from "../../../contracts/operations";
import { apiRequest } from "../../../lib/api/client";
import { advanceRiderSimulation, applyRefundDecision, applyWebhookSimulation } from "../../../lib/operations";

export const operationsHttpAdapter: OperationsAdapter = {
  async simulateRefund(order: OrderSummary, decision: RefundDecisionShape) {
    await apiRequest(`/orders/${order.orderId}/refunds`, {
      method: "POST",
      body: JSON.stringify({
        type: decision.type,
        amount: decision.amount,
        reason: decision.reason,
        requested_state: decision.refundState,
      }),
    });

    return applyRefundDecision(order, decision);
  },

  async simulateWebhook(order: OrderSummary, type: WebhookSimulationEvent["type"], payloadSummary: string) {
    await apiRequest("/simulation/ops/webhooks", {
      method: "POST",
      admin: true,
      body: JSON.stringify({
        event_type: type,
        payload: {
          order_id: order.orderId,
          summary: payloadSummary,
        },
      }),
    });

    return applyWebhookSimulation(order, type, payloadSummary);
  },

  async simulateRiderProgress(order: OrderSummary) {
    if (!order.deliveryAssignmentId) {
      await apiRequest(`/simulation/riders/orders/${order.orderId}/assignments`, {
        method: "POST",
        admin: true,
        body: JSON.stringify({ mode: "assign_first_available" }),
      });
    } else {
      await apiRequest(`/simulation/riders/assignments/${order.deliveryAssignmentId}/advance`, {
        method: "POST",
        admin: true,
        body: JSON.stringify({ mode: "next" }),
      });
    }

    return advanceRiderSimulation(order);
  },
};
