import type { OperationsAdapter } from "../../operationsService";
import type { OrderSummary } from "../../../contracts/order";
import type { RefundDecisionShape, WebhookSimulationEvent } from "../../../contracts/operations";
import { advanceRiderSimulation, applyRefundDecision, applyWebhookSimulation } from "../../../lib/operations";
import { inMemoryOrders } from "./orderLocalAdapter";

function persist(order: OrderSummary): OrderSummary {
  inMemoryOrders.set(order.orderId, order);
  return order;
}

export const operationsLocalAdapter: OperationsAdapter = {
  async simulateRefund(order, decision: RefundDecisionShape) {
    return persist(applyRefundDecision(order, decision));
  },

  async simulateWebhook(order, type: WebhookSimulationEvent["type"], payloadSummary: string) {
    return persist(applyWebhookSimulation(order, type, payloadSummary));
  },

  async simulateRiderProgress(order) {
    return persist(advanceRiderSimulation(order));
  },
};
