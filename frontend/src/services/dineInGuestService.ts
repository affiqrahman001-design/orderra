import type { TableQrSession } from "../contracts/order";
import { apiRequest } from "../lib/api/client";

export interface GuestQrSessionDto {
  id: string;
  session_code: string;
  join_url: string;
  public_qr_url?: string | null;
  status: string;
  can_order?: boolean;
  allowed_actions?: { attach_cart?: boolean };
  table: { label?: string | null; code?: string | null } | null;
  branch: { id?: string; code?: string | null; name?: string | null } | null;
  linked_orders?: Array<{ order_id?: string | null }>;
}

type Envelope<T> = { data: T };

function unwrapApiInner<T>(payload: Envelope<T | { data?: T }>): T {
  const v: unknown = payload.data;

  if (v && typeof v === "object" && v !== null && "data" in v) {
    const nested = (v as { data?: T }).data;
    if (nested !== undefined && typeof nested === "object" && nested !== null) {
      return nested;
    }
  }

  return v as T;
}

export async function fetchGuestQrSessionByCode(sessionCode: string): Promise<GuestQrSessionDto> {
  const code = encodeURIComponent(sessionCode.trim());
  const envelope = await apiRequest<Envelope<GuestQrSessionDto | { data: GuestQrSessionDto }>>(
    `/qr/${code}`,
  );
  return unwrapApiInner<GuestQrSessionDto>(envelope);
}

export function mapGuestQrSessionDto(dto: GuestQrSessionDto): TableQrSession {
  const tableRef = dto.table?.label?.trim() || dto.table?.code?.trim() || "Table";

  let status: TableQrSession["status"] = "closed";
  if (dto.status === "open") status = "open";
  else if (dto.status === "bill_requested") status = "bill_requested";
  else if (dto.status === "payment_ready") status = "payment_ready";

  const activeOrderIds =
    dto.linked_orders
      ?.map((entry) => entry.order_id?.trim())
      .filter((id): id is string => typeof id === "string" && id.length > 0) ?? [];

  return {
    sessionId: dto.id,
    tableReference: tableRef,
    qrSessionCode: dto.session_code,
    joinUrl: dto.public_qr_url?.trim() || dto.join_url,
    status,
    activeOrderIds,
    actionHistory: [
      {
        type: "session_created",
        occurredAt: new Date().toISOString(),
        note:
          dto.branch?.name ?
            `Linked to ${dto.branch.name} · ${dto.table?.label ?? dto.table?.code ?? "table"}`
          : `Linked to dine-in session ${dto.session_code}.`,
      },
    ],
  };
}

export async function attachCartToGuestQrSession(sessionPublicId: string, cartToken: string): Promise<void> {
  await apiRequest(`/dine-in/sessions/${sessionPublicId}/attach-cart`, {
    method: "POST",
    cartToken,
    body: JSON.stringify({ cart_token: cartToken }),
  });
}

export type JoinTableQrSummary = {
  joinUrl: string;
  sessionCodeDisplay: string;
  tableReference: string;
  branchName?: string;
  canOrder: boolean;
};

export function buildJoinTableQrSummary(dto: GuestQrSessionDto, session: TableQrSession): JoinTableQrSummary {
  return {
    joinUrl: dto.public_qr_url?.trim() || dto.join_url,
    sessionCodeDisplay: dto.session_code,
    tableReference: session.tableReference,
    branchName: dto.branch?.name ?? undefined,
    canOrder: Boolean(dto.can_order),
  };
}
