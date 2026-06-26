import type { PaymentMethodCode } from "./order";

export type PaymentState = "pending" | "authorized" | "succeeded" | "failed" | "cancelled";
export type PaymentSimulationOutcome = "success" | "failed" | "pending";

export type DemoPaymentProviderCode =
  | "demo_card"
  | "demo_cash"
  | "demo_wallet"
  | "demo_bank"
  | "demo_paypal"
  | "demo_malaysia";

export interface PaymentSimulationRequest {
  method: PaymentMethodCode;
  amount: number;
  currency: "USD";
  liveExecution?: boolean;
  reference: string;
  cartToken?: string | null;
  simulationOutcome?: PaymentSimulationOutcome;
}

export interface PaymentSimulationResult {
  state: PaymentState;
  method: PaymentMethodCode;
  provider: DemoPaymentProviderCode;
  amount: number;
  currency: "USD";
  reference: string;
  simulatedAt: string;
  message: string;
  demoMode: true;
  paymentIntentId?: string;
  paymentIntentCode?: string;
  providerReference?: string;
  canPlaceOrder?: boolean;
}
