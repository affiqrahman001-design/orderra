import type { OrderStatusCode, PaymentMethodCode } from '../contracts/order';
import type {
  DemoPaymentProviderCode,
  PaymentSimulationOutcome,
  PaymentSimulationRequest,
  PaymentSimulationResult,
  PaymentState,
} from '../contracts/payment';

const PAYMENT_METHOD_LABELS: Record<PaymentMethodCode, string> = {
  card: 'Card',
  apple_pay: 'Apple Pay',
  google_pay: 'Google Pay',
  ach: 'ACH bank payment',
  cash: 'Cash',
  paypal: 'PayPal',
  fpx: 'FPX',
  duitnow_qr: 'DuitNow QR',
};

const PAYMENT_STATE_LABELS: Record<PaymentState, string> = {
  pending: 'Pending',
  authorized: 'Authorized',
  succeeded: 'Succeeded',
  failed: 'Failed',
  cancelled: 'Cancelled',
};

const DEMO_PAYMENT_PROVIDER_BY_METHOD: Record<PaymentMethodCode, DemoPaymentProviderCode> = {
  card: 'demo_card',
  cash: 'demo_cash',
  apple_pay: 'demo_wallet',
  google_pay: 'demo_wallet',
  ach: 'demo_bank',
  paypal: 'demo_paypal',
  fpx: 'demo_malaysia',
  duitnow_qr: 'demo_malaysia',
};

export function getPaymentMethodLabel(method: PaymentMethodCode): string {
  return PAYMENT_METHOD_LABELS[method];
}

export function getPaymentStateLabel(state: PaymentState): string {
  return PAYMENT_STATE_LABELS[state];
}

export function getPaymentSimulationMessage(
  method: PaymentMethodCode,
  state: PaymentState,
): string {
  const methodLabel = getPaymentMethodLabel(method);

  if (state === 'pending') {
    return `${methodLabel} is still pending in demo mode. No order is placed until the payment is approved.`;
  }

  if (state === 'failed' || state === 'cancelled') {
    return `${methodLabel} was declined in demo mode. No order was placed and no real charge was made.`;
  }

  if (state === 'authorized') {
    return `${methodLabel} was authorized in demo mode. No live capture was performed.`;
  }

  return `${methodLabel} completed through a demo-safe provider placeholder. No live payment was charged.`;
}

export function getDemoPaymentProvider(method: PaymentMethodCode): DemoPaymentProviderCode {
  return DEMO_PAYMENT_PROVIDER_BY_METHOD[method];
}

export function assertDemoSafePaymentRequest(input: PaymentSimulationRequest): void {
  if (input.liveExecution) {
    throw new Error(
      'Live execution is blocked. ORDERra frontend only supports demo-safe payment simulation.',
    );
  }
}

export function getDefaultPaymentState(
  method: PaymentMethodCode,
  outcome: PaymentSimulationOutcome = 'success',
): PaymentState {
  if (outcome === 'failed') return 'failed';
  if (outcome === 'pending') return 'pending';
  if (method === 'card' || method === 'apple_pay' || method === 'google_pay' || method === 'paypal')
    return 'authorized';
  return 'succeeded';
}

export function buildPaymentResult(
  input: PaymentSimulationRequest,
  state: PaymentState,
  message: string,
): PaymentSimulationResult {
  return {
    state,
    method: input.method,
    provider: getDemoPaymentProvider(input.method),
    amount: input.amount,
    currency: input.currency,
    reference: input.reference,
    simulatedAt: new Date().toISOString(),
    message,
    demoMode: true,
    canPlaceOrder: state === 'authorized' || state === 'succeeded',
  };
}

export function canCreateOrderFromPaymentState(state: PaymentState): boolean {
  return state === 'authorized' || state === 'succeeded';
}

export function getOrderStatusFromPaymentState(state: PaymentState): OrderStatusCode {
  if (state === 'authorized') return 'payment_authorized';
  if (state === 'pending') return 'pending_payment';
  if (state === 'failed' || state === 'cancelled') return 'cancelled';
  return 'placed';
}
