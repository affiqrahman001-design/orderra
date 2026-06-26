export * from "./types";
export * from "./api/adminReferenceApi";

export type AdminListMeta = {
  current_page: number;
  per_page: number;
  total: number;
  last_page: number;
};

export type AdminOrderSummary = {
  id: string;
  order_code: string;
  status: string;
  fulfillment_type: string;
  source: string;
  currency: string;
  item_count: number;
  total_amount: number;
  customer_name?: string | null;
  allowed_transitions: string[];
  placed_at?: string | null;
  completed_at?: string | null;
};

export type AdminPaymentLogSummary = {
  id: string;
  intent_code: string;
  status: string;
  method_code: string;
  provider_code: string;
  currency: string;
  amount: number;
  attempts_count: number;
  transactions_count: number;
  refunds_count: number;
  support_tickets_count: number;
  created_at?: string | null;
  last_attempted_at?: string | null;
};

export type AdminRefundSummary = {
  id: string;
  category: string;
  status: string;
  resolution_type?: string | null;
  currency: string;
  requested_amount: number;
  approved_amount?: number | null;
  resolved_amount?: number | null;
  requested_at?: string | null;
};

export type AdminSupportTicketSummary = {
  id: string;
  category: string;
  status: string;
  subject: string;
  opened_at?: string | null;
  resolved_at?: string | null;
  closed_at?: string | null;
};

export type AdminWebhookSummary = {
  id: string;
  event_name: string;
  aggregate_type: string;
  status: string;
  replay_count: number;
  generated_at?: string | null;
};

export type AdminDashboardResponse = {
  data: {
    counters: Record<string, number>;
    watchlists: Record<string, Record<string, number>>;
    recent_orders: AdminOrderSummary[];
    recent_payments: AdminPaymentLogSummary[];
    recent_refunds: AdminRefundSummary[];
    recent_support_tickets: AdminSupportTicketSummary[];
    recent_webhooks: AdminWebhookSummary[];
  };
};

export type AdminPaginatedResponse<T> = {
  data: T[];
  meta: AdminListMeta;
};

export type AdminDemoScenariosResponse = {
  data: {
    guards: Record<string, boolean>;
    simulation_rules: Record<string, string[]>;
    scenarios: Array<{
      key: string;
      label: string;
      method: string;
      path: string;
      notes: string;
    }>;
  };
};
