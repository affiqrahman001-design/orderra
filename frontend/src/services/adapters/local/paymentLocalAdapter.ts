import type { PaymentSimulationRequest } from '../../../contracts/payment';
import {
  assertDemoSafePaymentRequest,
  buildPaymentResult,
  getDefaultPaymentState,
  getPaymentSimulationMessage,
} from '../../../lib/payment';
import type { PaymentAdapter } from '../../paymentService';

export const paymentLocalAdapter: PaymentAdapter = {
  async simulatePayment(input: PaymentSimulationRequest) {
    assertDemoSafePaymentRequest(input);

    const state = getDefaultPaymentState(input.method, input.simulationOutcome);
    return buildPaymentResult(input, state, getPaymentSimulationMessage(input.method, state));
  },
};
