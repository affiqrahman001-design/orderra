import type { AdminOrderDetail } from "../admin-reference/types";

/** Normalizes Laravel OrderResource payloads whether single- or double-wrapped. */
export function unwrapAdminOrderEnvelope(payload: unknown): AdminOrderDetail {
  if (!payload || typeof payload !== "object") throw new Error("Invalid order payload");

  const inner: unknown = (payload as { data?: unknown }).data;

  if (inner && typeof inner === "object" && inner !== null && "data" in inner) {
    const nested = (inner as { data?: AdminOrderDetail }).data;
    if (nested && typeof nested === "object") return nested;
  }

  if (!inner || typeof inner !== "object") {
    throw new Error("Malformed order envelope");
  }

  return inner as AdminOrderDetail;
}
