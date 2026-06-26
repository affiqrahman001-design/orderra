import { useEffect, useState } from "react";
import { ApiError } from "../../lib/api/client";
import { formatCurrency } from "../../lib/currency";
import { adminReferenceApi } from "../admin-reference";
import type { AdminOrderSummary, AdminPaginatedResponse } from "../admin-reference/types";
import { FULFILLMENT_FILTER_OPTIONS, ORDER_STATUS_FILTER_OPTIONS, humanizeAdminStatus } from "./adminOrdersConstants";
import type { AdminNavigate } from "./AdminLoginPage";

function summarizeError(err: unknown, fallback: string): string {
  if (err instanceof ApiError) return getPayloadMessage(err.payload, fallback);
  if (err instanceof Error && err.message) return err.message;
  return fallback;
}

function getPayloadMessage(payload: unknown, fallback: string): string {
  if (!payload || typeof payload !== "object") return fallback;
  const record = payload as Record<string, unknown>;
  if (typeof record.message === "string" && record.message.trim()) return record.message;
  const errors = record.errors;
  if (errors && typeof errors === "object") {
    const first = Object.values(errors as Record<string, unknown>)[0];
    if (Array.isArray(first) && typeof first[0] === "string") return first[0];
  }
  return fallback;
}

export function AdminOrdersPage({ navigate }: { navigate: AdminNavigate }) {
  const [rows, setRows] = useState<AdminOrderSummary[]>([]);
  const [meta, setMeta] = useState<AdminPaginatedResponse<AdminOrderSummary>["meta"] | null>(null);
  const [page, setPage] = useState(1);
  const [status, setStatus] = useState("");
  const [fulfillmentType, setFulfillmentType] = useState("");
  const [q, setQ] = useState("");
  const [qApplied, setQApplied] = useState("");
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    const handle = window.setTimeout(() => setQApplied(q.trim()), 350);
    return () => window.clearTimeout(handle);
  }, [q]);

  useEffect(() => {
    setPage(1);
  }, [status, fulfillmentType, qApplied]);

  useEffect(() => {
    let cancelled = false;
    setLoading(true);
    setError(null);

    adminReferenceApi
      .listOrders({
        page,
        per_page: 20,
        ...(status ? { status } : {}),
        ...(fulfillmentType ? { fulfillment_type: fulfillmentType } : {}),
        ...(qApplied ? { q: qApplied } : {}),
      })
      .then((res) => {
        if (!cancelled) {
          setRows(res.data);
          setMeta(res.meta);
        }
      })
      .catch((err: unknown) => {
        if (!cancelled) setError(summarizeError(err, "Orders could not be loaded."));
      })
      .finally(() => {
        if (!cancelled) setLoading(false);
      });

    return () => {
      cancelled = true;
    };
  }, [page, status, fulfillmentType, qApplied]);

  return (
    <div className="admin-page">
      <div className="section-heading section-heading--compact">
        <h3>Orders</h3>
        <p>Search by order code or public id, filter by status or fulfillment mode, then open an order for transitions.</p>
      </div>

      {error ? (
        <div className="inline-notice inline-notice--error">
          <p>{error}</p>
        </div>
      ) : null}

      <div className="form-card admin-orders__filters">
        <div className="form-grid" style={{ gridTemplateColumns: "repeat(auto-fit, minmax(160px, 1fr))" }}>
          <label className="field">
            <span>Search</span>
            <input
              type="search"
              autoComplete="off"
              placeholder="Order code / id fragment"
              value={q}
              onChange={(e) => setQ(e.target.value)}
            />
          </label>
          <label className="field">
            <span>Status</span>
            <select value={status} onChange={(e) => setStatus(e.target.value)}>
              {ORDER_STATUS_FILTER_OPTIONS.map((o) => (
                <option key={o.value || "any"} value={o.value}>
                  {o.label}
                </option>
              ))}
            </select>
          </label>
          <label className="field">
            <span>Fulfillment</span>
            <select value={fulfillmentType} onChange={(e) => setFulfillmentType(e.target.value)}>
              {FULFILLMENT_FILTER_OPTIONS.map((o) => (
                <option key={o.value || "any_f"} value={o.value}>
                  {o.label}
                </option>
              ))}
            </select>
          </label>
        </div>
      </div>

      {loading && !rows.length ? <p className="admin-muted">Loading orders…</p> : null}

      {!loading && !rows.length && !error ? (
        <p className="admin-muted">No orders match these filters.</p>
      ) : null}

      {rows.length > 0 ? (
        <>
          <div className="admin-table-wrap">
            <table className="admin-data-table">
              <thead>
                <tr>
                  <th>Code</th>
                  <th>Status</th>
                  <th>Mode</th>
                  <th>Customer</th>
                  <th>Items</th>
                  <th>Total</th>
                  <th></th>
                </tr>
              </thead>
              <tbody>
                {rows.map((row) => (
                  <tr key={row.id}>
                    <td>
                      <strong>{row.order_code}</strong>
                    </td>
                    <td>{humanizeAdminStatus(row.status)}</td>
                    <td>{humanizeAdminStatus(row.fulfillment_type)}</td>
                    <td>{row.customer_name?.trim() || "—"}</td>
                    <td>{row.item_count}</td>
                    <td>{formatCurrency(row.total_amount)}</td>
                    <td>
                      <button
                        type="button"
                        className="button button--quiet"
                        onClick={() => navigate(`/admin/orders/${encodeURIComponent(row.id)}`)}
                      >
                        Open
                      </button>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>

          {meta && meta.last_page > 1 ? (
            <div className="admin-pagination">
              <button
                type="button"
                className="button button--quiet"
                disabled={page <= 1 || loading}
                onClick={() => setPage((p) => Math.max(1, p - 1))}
              >
                Previous
              </button>
              <span className="admin-muted">
                Page {meta.current_page} of {meta.last_page} · {meta.total} total
              </span>
              <button
                type="button"
                className="button button--quiet"
                disabled={page >= meta.last_page || loading}
                onClick={() => setPage((p) => p + 1)}
              >
                Next
              </button>
            </div>
          ) : null}
        </>
      ) : null}
    </div>
  );
}
