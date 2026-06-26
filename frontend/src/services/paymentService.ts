import type { PaymentSimulationRequest, PaymentSimulationResult } from "../contracts/payment";

export interface PaymentAdapter {
  simulatePayment(input: PaymentSimulationRequest): Promise<PaymentSimulationResult>;
}

export interface PaymentService {
  simulatePayment(input: PaymentSimulationRequest): Promise<PaymentSimulationResult>;
}

export function createPaymentService(adapter: PaymentAdapter): PaymentService {
  return {
    simulatePayment: (input) => adapter.simulatePayment(input),
  };
}
