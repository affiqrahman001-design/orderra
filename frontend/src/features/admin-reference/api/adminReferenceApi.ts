import type {
  AdminDashboardResponse,
  AdminDemoScenariosResponse,
  AdminOrderSummary,
  AdminPaginatedResponse,
  AdminPaymentLogSummary,
  AdminRefundSummary,
  AdminSupportTicketSummary,
  AdminWebhookSummary,
  AdminAuditLogResponse,
  AdminAuditLogSummary,
  AdminNotificationLogResponse,
  AdminNotificationLogSummary,
  AdminBranchResponse,
  AdminBranchSummary,
  AdminDeliveryZoneResponse,
  AdminDeliveryZoneSummary,
  AdminFeeRuleResponse,
  AdminFeeRuleSummary,
  AdminTaxRuleResponse,
  AdminTaxRuleSummary,
  AdminQrSessionSummary,
  AdminRestaurantTableRow,
} from "../types";
import { apiRequest } from "../../../lib/api/client";

function readJson<T>(path: string, init?: RequestInit): Promise<T> {
  return apiRequest<T>(path, { ...init, admin: true });
}

function buildQuery(params?: Record<string, string | number | undefined | null>): string {
  if (!params) return "";

  const query = new URLSearchParams();

  Object.entries(params).forEach(([key, value]) => {
    if (value !== undefined && value !== null && value !== "") {
      query.set(key, String(value));
    }
  });

  const serialized = query.toString();

  return serialized ? `?${serialized}` : "";
}

export const adminReferenceApi = {
  getDashboard(): Promise<AdminDashboardResponse> {
    return readJson("/admin/dashboard");
  },

  getDemoScenarios(): Promise<AdminDemoScenariosResponse> {
    return readJson("/admin/demo-scenarios");
  },

  listOrders(params?: Record<string, string | number | undefined | null>) {
    return readJson<AdminPaginatedResponse<AdminOrderSummary>>(
      `/admin/orders${buildQuery(params)}`
    );
  },

  listPayments(params?: Record<string, string | number | undefined | null>) {
    return readJson<AdminPaginatedResponse<AdminPaymentLogSummary>>(
      `/admin/payments/intents${buildQuery(params)}`
    );
  },

  listRefunds(params?: Record<string, string | number | undefined | null>) {
    return readJson<AdminPaginatedResponse<AdminRefundSummary>>(
      `/admin/refunds${buildQuery(params)}`
    );
  },

  listSupportTickets(params?: Record<string, string | number | undefined | null>) {
    return readJson<AdminPaginatedResponse<AdminSupportTicketSummary>>(
      `/admin/support/tickets${buildQuery(params)}`
    );
  },

  listWebhooks(params?: Record<string, string | number | undefined | null>) {
    return readJson<AdminPaginatedResponse<AdminWebhookSummary>>(
      `/admin/webhooks${buildQuery(params)}`
    );
  },

  getOrder(orderId: string) {
    return readJson(`/admin/orders/${orderId}`);
  },

  transitionOrderStatus(
    orderId: string,
    body: { to_status: string; reason?: string | null; meta?: Record<string, unknown> },
  ) {
    return readJson(`/admin/orders/${orderId}/status`, {
      method: "POST",
      body: JSON.stringify({
        to_status: body.to_status,
        reason: body.reason ?? "Updated from ORDERra admin panel.",
        meta: body.meta ?? {},
      }),
    });
  },

  getPaymentIntent(paymentIntentId: string) {
    return readJson(`/admin/payments/intents/${paymentIntentId}`);
  },

  getRefund(refundId: string) {
    return readJson(`/admin/refunds/${refundId}`);
  },

  getSupportTicket(ticketId: string) {
    return readJson(`/admin/support/tickets/${ticketId}`);
  },

  getWebhook(webhookId: string) {
    return readJson(`/admin/webhooks/${webhookId}`);
  },

  getQrSession(qrSessionId: string) {
    return readJson(`/admin/dine-in/sessions/${qrSessionId}`);
  },

  listQrSessions(params?: Record<string, string | number | undefined | null>) {
    return readJson<AdminPaginatedResponse<AdminQrSessionSummary>>(
      `/admin/dine-in/sessions${buildQuery(params)}`,
    );
  },

  listRestaurantTables(params?: Record<string, string | number | undefined | null>) {
    return readJson<AdminPaginatedResponse<AdminRestaurantTableRow>>(
      `/admin/tables${buildQuery(params)}`,
    );
  },

  rotateTableQrSession(
    tableId: string,
    body?: { party_size?: number | null; note?: string | null },
  ): Promise<{ data: Record<string, unknown> } | Record<string, unknown>> {
    return readJson(`/admin/tables/${tableId}/qr-session`, {
      method: "POST",
      body: JSON.stringify({
        party_size: body?.party_size ?? null,
        note: body?.note ?? null,
      }),
    });
  },

  listRiderAssignments(params?: Record<string, string | number | undefined | null>) {
    return readJson<AdminPaginatedResponse<Record<string, unknown>>>(
      `/admin/riders/assignments${buildQuery(params)}`,
    );
  },

  listRiders(params?: Record<string, string | number | undefined | null>) {
    return readJson<AdminPaginatedResponse<Record<string, unknown>>>(
      `/admin/riders${buildQuery(params)}`,
    );
  },

  getDeliveryAssignment(deliveryAssignmentId: string) {
    return readJson(`/admin/riders/assignments/${deliveryAssignmentId}`);
  },

  getRiderPool() {
    return readJson(`/admin/riders/pool`);
  },

  listAuditLogs(params?: Record<string, string | number | undefined | null>) {
    return readJson<AdminPaginatedResponse<AdminAuditLogSummary>>(
      `/admin/audit-logs${buildQuery(params)}`
    );
  },

  getAuditLog(auditLogId: string) {
    return readJson<AdminAuditLogResponse>(`/admin/audit-logs/${auditLogId}`);
  },

  listNotificationLogs(params?: Record<string, string | number | undefined | null>) {
    return readJson<AdminPaginatedResponse<AdminNotificationLogSummary>>(
      `/admin/notification-logs${buildQuery(params)}`
    );
  },

  getNotificationLog(notificationLogId: string) {
    return readJson<AdminNotificationLogResponse>(
      `/admin/notification-logs/${notificationLogId}`
    );
  },

  listBranches(params?: Record<string, string | number | undefined | null>) {
    return readJson<AdminPaginatedResponse<AdminBranchSummary>>(
      `/admin/branches${buildQuery(params)}`
    );
  },

  getBranch(branchId: string) {
    return readJson<AdminBranchResponse>(`/admin/branches/${branchId}`);
  },

  listDeliveryZones(params?: Record<string, string | number | undefined | null>) {
    return readJson<AdminPaginatedResponse<AdminDeliveryZoneSummary>>(
      `/admin/delivery-zones${buildQuery(params)}`
    );
  },

  getDeliveryZone(deliveryZoneId: string) {
    return readJson<AdminDeliveryZoneResponse>(`/admin/delivery-zones/${deliveryZoneId}`);
  },

  listTaxRules(params?: Record<string, string | number | undefined | null>) {
    return readJson<AdminPaginatedResponse<AdminTaxRuleSummary>>(
      `/admin/tax-rules${buildQuery(params)}`
    );
  },

  getTaxRule(taxRuleId: string) {
    return readJson<AdminTaxRuleResponse>(`/admin/tax-rules/${taxRuleId}`);
  },

  listFeeRules(params?: Record<string, string | number | undefined | null>) {
    return readJson<AdminPaginatedResponse<AdminFeeRuleSummary>>(
      `/admin/fee-rules${buildQuery(params)}`
    );
  },

  getFeeRule(feeRuleId: string) {
    return readJson<AdminFeeRuleResponse>(`/admin/fee-rules/${feeRuleId}`);
  },
};
