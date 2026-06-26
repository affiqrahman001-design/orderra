import type {
  PaymentSimulationRequest,
  PaymentSimulationResult,
  PaymentState,
} from '../../../contracts/payment';
import { apiRequest } from '../../../lib/api/client';
import {
  assertDemoSafePaymentRequest,
  getDemoPaymentProvider,
  getPaymentSimulationMessage,
} from '../../../lib/payment';
import type { PaymentAdapter } from '../../paymentService';
import { getStoredCartToken } from './cartHttpAdapter';

type BackendIntent = {
  id?: string;
  public_id?: string;
  intent_code?: string;
  code?: string;
  status?: PaymentState;
  method_code?: string;
  amount?: number;
  currency?: 'USD';
  provider_reference?: string | null;
};

type BackendSimulationPayload = {
  intent?: BackendIntent;
  result?: {
    outcome?: string;
    payment_status?: PaymentState;
    can_place_order?: boolean;
    provider_reference?: string | null;
  };
};

type BackendEnvelope<T> = { data: T; message?: string };

function normalizeBackendAmount(amount: number | undefined, fallback: number): number {
  if (amount === undefined || amount === null) return fallback;
  return amount > 100 ? Math.round(amount) / 100 : amount;
}

function resolvePublicIntentId(intent: BackendIntent): string | undefined {
  return intent.public_id ?? intent.id;
}

function mapPayment(
  input: PaymentSimulationRequest,
  intent: BackendIntent,
  result?: BackendSimulationPayload['result'],
): PaymentSimulationResult {
  const state = result?.payment_status ?? intent.status ?? 'pending';
  const providerReference = result?.provider_reference ?? intent.provider_reference ?? undefined;

  return {
    state,
    method: input.method,
    provider: getDemoPaymentProvider(input.method),
    amount: normalizeBackendAmount(intent.amount, input.amount),
    currency: intent.currency ?? input.currency,
    reference: input.reference,
    simulatedAt: new Date().toISOString(),
    message: getPaymentSimulationMessage(input.method, state),
    demoMode: true,
    paymentIntentId: resolvePublicIntentId(intent),
    paymentIntentCode: intent.intent_code ?? intent.code,
    providerReference: providerReference ?? undefined,
    canPlaceOrder: result?.can_place_order ?? (state === 'authorized' || state === 'succeeded'),
  };
}

export const paymentHttpAdapter: PaymentAdapter = {
  async simulatePayment(input: PaymentSimulationRequest) {
    assertDemoSafePaymentRequest(input);

    const cartToken = input.cartToken ?? getStoredCartToken();

    const intentResponse = await apiRequest<BackendEnvelope<BackendIntent>>('/payments/intents', {
      method: 'POST',
      cartToken,
      body: JSON.stringify({
        method_code: input.method,
        country_code: input.method === 'fpx' || input.method === 'duitnow_qr' ? 'MY' : 'US',
        currency: input.currency,
        cart_token: cartToken,
        simulation_outcome: input.simulationOutcome ?? 'success',
        meta: {
          frontend_reference: input.reference,
        },
      }),
    });

    const intentId = resolvePublicIntentId(intentResponse.data);
    if (!intentId) throw new Error('Payment intent id is missing from backend response.');

    const simulatedResponse = await apiRequest<BackendEnvelope<BackendSimulationPayload>>(
      `/payments/intents/${intentId}/simulate`,
      {
        method: 'POST',
        cartToken,
        body: JSON.stringify({ simulation_outcome: input.simulationOutcome ?? 'success' }),
      },
    );

    const simulatedIntent = simulatedResponse.data.intent ?? intentResponse.data;

    return mapPayment(input, simulatedIntent, simulatedResponse.data.result);
  },
};
