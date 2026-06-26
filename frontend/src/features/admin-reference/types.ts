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

export type AdminOrderLineItem = {
  id: string | number;
  item_name: string;
  item_slug?: string;
  quantity: number;
  unit_price: number;
  line_subtotal?: number;
  image_url?: string | null;
  note?: string | null;
};

export type AdminOrderDetail = {
  id: string;
  order_code: string;
  status: string;
  allowed_transitions: string[];
  currency: string;
  fulfillment_type: string;
  source?: string;
  placed_at?: string | null;
  completed_at?: string | null;
  cancelled_at?: string | null;
  customer_context?: Record<string, unknown>;
  fulfillment_context?: Record<string, unknown>;
  totals: {
    subtotal: number;
    discount: number;
    service_fee: number;
    delivery_fee: number;
    small_order_fee?: number;
    tax?: number;
    tip?: number;
    total: number;
  };
  items: AdminOrderLineItem[];
  fulfillment?: Record<string, unknown> | null;
  status_history?: Array<Record<string, unknown>>;
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

export type AdminAuditLogSummary = {
  id: string;
  channel: string;
  action: string;
  status: string;
  actor_type?: string | null;
  entity_type?: string | null;
  entity_public_id?: string | null;
  entity_secondary_key?: string | null;
  summary?: string | null;
  occurred_at?: string | null;
};

export type AdminNotificationLogSummary = {
  id: string;
  channel: string;
  notification_type: string;
  status: string;
  recipient_type?: string | null;
  recipient_key?: string | null;
  entity_type?: string | null;
  entity_public_id?: string | null;
  title?: string | null;
  subject?: string | null;
  sent_at?: string | null;
  failed_at?: string | null;
  created_at?: string | null;
};

export type AdminAuditLogResponse = {
  data: {
    id: string;
    channel: string;
    action: string;
    status: string;
    actor_type?: string | null;
    actor_id?: string | null;
    entity_type?: string | null;
    entity_public_id?: string | null;
    entity_secondary_key?: string | null;
    summary?: string | null;
    request_method?: string | null;
    request_path?: string | null;
    request_snapshot: Record<string, unknown>;
    context_snapshot: Record<string, unknown>;
    occurred_at?: string | null;
    created_at?: string | null;
    updated_at?: string | null;
  };
};

export type AdminNotificationLogResponse = {
  data: {
    id: string;
    channel: string;
    notification_type: string;
    status: string;
    provider_code?: string | null;
    recipient_type?: string | null;
    recipient_key?: string | null;
    entity_type?: string | null;
    entity_public_id?: string | null;
    subject?: string | null;
    title?: string | null;
    body_preview?: string | null;
    meta: Record<string, unknown>;
    error_message?: string | null;
    sent_at?: string | null;
    failed_at?: string | null;
    created_at?: string | null;
    updated_at?: string | null;
  };
};

export type AdminQrSessionSummary = {
  id: string;
  session_code: string;
  status: string;
  party_size?: number;
  join_url: string;
  public_qr_url?: string | null;
  expires_at?: string | null;
  table?: { id: string; code: string; label: string } | null;
  linked_orders_count?: number;
  linked_carts_count?: number;
  active_split_bill?: { id: string; status: string; split_type: string } | null;
  opened_at?: string | null;
  last_activity_at?: string | null;
  bill_requested_at?: string | null;
  closed_at?: string | null;
};

export type AdminBranchSummary = {
  id: string;
  code: string;
  name: string;
  status: string;
  country_code: string;
  currency: string;
  timezone: string;
  supports_delivery: boolean;
  supports_pickup: boolean;
  supports_dine_in: boolean;
  is_default: boolean;
  city?: string | null;
  state?: string | null;
  created_at?: string | null;
};

export type AdminDeliveryZoneSummary = {
  id: string;
  branch_code: string;
  code: string;
  name: string;
  status: string;
  pricing_strategy: string;
  minimum_order_amount?: number | null;
  base_fee_amount: number;
  fee_per_km_amount?: number | null;
  free_delivery_threshold_amount?: number | null;
  estimated_minutes?: number | null;
  sort_order: number;
  created_at?: string | null;
};

export type AdminTaxRuleSummary = {
  id: string;
  branch_id?: number | null;
  branch_code?: string | null;
  country_code?: string | null;
  state_code?: string | null;
  city_code?: string | null;
  fulfillment_type?: string | null;
  name: string;
  rate_bps?: number | null;
  percentage_rate?: number | null;
  applies_to_subtotal: boolean;
  applies_to_service_fee: boolean;
  applies_to_delivery_fee: boolean;
  applies_to_small_order_fee: boolean;
  priority: number;
  is_active: boolean;
  created_at?: string | null;
};

export type AdminFeeRuleSummary = {
  id: string;
  branch_id?: number | null;
  branch_code?: string | null;
  code: string;
  name: string;
  fee_kind: string;
  fulfillment_type?: string | null;
  calculation_type: string;
  fixed_amount?: number | null;
  percentage_bps?: number | null;
  percentage_rate?: number | null;
  threshold_amount?: number | null;
  min_amount?: number | null;
  max_amount?: number | null;
  taxable: boolean;
  conditions_json?: Record<string, unknown> | unknown[] | null;
  priority: number;
  is_active: boolean;
  created_at?: string | null;
};

export type AdminBranchResponse = { data: AdminBranchSummary & Record<string, unknown> };
export type AdminDeliveryZoneResponse = { data: AdminDeliveryZoneSummary & Record<string, unknown> };
export type AdminTaxRuleResponse = { data: AdminTaxRuleSummary & Record<string, unknown> };
export type AdminFeeRuleResponse = { data: AdminFeeRuleSummary & Record<string, unknown> };

export type AdminRestaurantTableActiveQr = {
  id: string;
  session_code: string;
  status: string;
  join_url: string;
  public_qr_url?: string | null;
  party_size?: number;
  opened_at?: string | null;
  last_activity_at?: string | null;
  expires_at?: string | null;
};

export type AdminRestaurantTableRow = {
  id: string;
  code: string;
  label: string;
  seat_capacity: number;
  status: string;
  branch?: { id: string; code: string; name: string } | null;
  active_qr_session: AdminRestaurantTableActiveQr | null;
};
