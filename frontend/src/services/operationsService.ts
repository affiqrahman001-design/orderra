import type { OrderSummary } from "../contracts/order";
import type { RefundDecisionShape, WebhookSimulationEvent } from "../contracts/operations";

export interface OperationsAdapter {
  simulateRefund(order: OrderSummary, decision: RefundDecisionShape): Promise<OrderSummary>;
  simulateWebhook(
    order: OrderSummary,
    type: WebhookSimulationEvent["type"],
    payloadSummary: string,
  ): Promise<OrderSummary>;
  simulateRiderProgress(order: OrderSummary): Promise<OrderSummary>;
}

export interface OperationsService {
  simulateRefund(order: OrderSummary, decision: RefundDecisionShape): Promise<OrderSummary>;
  simulateWebhook(
    order: OrderSummary,
    type: WebhookSimulationEvent["type"],
    payloadSummary: string,
  ): Promise<OrderSummary>;
  simulateRiderProgress(order: OrderSummary): Promise<OrderSummary>;
}

export function createOperationsService(adapter: OperationsAdapter): OperationsService {
  return {
    simulateRefund: (order, decision) => adapter.simulateRefund(order, decision),
    simulateWebhook: (order, type, payloadSummary) => adapter.simulateWebhook(order, type, payloadSummary),
    simulateRiderProgress: (order) => adapter.simulateRiderProgress(order),
  };
}
