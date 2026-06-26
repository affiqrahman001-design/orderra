import { useCallback, useEffect, useMemo, useState } from 'react';
import { formatCurrency } from '../../lib/currency';
import { adminReferenceApi } from '../admin-reference';
import type { AdminOrderSummary } from '../admin-reference/types';
import type { AdminNavigate } from './AdminLoginPage';
import { humanizeAdminStatus } from './adminOrdersConstants';

const BOARD_COLUMNS = [
  { status: 'confirmed', label: 'Confirmed', hint: 'Accepted orders waiting for kitchen prep.' },
  { status: 'preparing', label: 'Preparing', hint: 'Orders currently being prepared.' },
  { status: 'ready', label: 'Ready', hint: 'Ready for pickup, dine-in serving, or rider handoff.' },
] as const;

type BoardColumnStatus = (typeof BOARD_COLUMNS)[number]['status'];
type KitchenBoardState = Record<BoardColumnStatus, AdminOrderSummary[]>;

function createEmptyBoard(): KitchenBoardState {
  return BOARD_COLUMNS.reduce((acc, column) => {
    acc[column.status] = [];
    return acc;
  }, {} as KitchenBoardState);
}

function formatLastUpdated(value: Date | null): string {
  if (!value) return 'Not refreshed yet';

  return value.toLocaleTimeString([], {
    hour: '2-digit',
    minute: '2-digit',
    second: '2-digit',
  });
}

export function AdminKitchenPage({ navigate }: { navigate: AdminNavigate }) {
  const [byStatus, setByStatus] = useState<KitchenBoardState>(() => createEmptyBoard());
  const [error, setError] = useState<string | null>(null);
  const [loading, setLoading] = useState(true);
  const [lastUpdated, setLastUpdated] = useState<Date | null>(null);

  const totalVisibleOrders = useMemo(
    () => BOARD_COLUMNS.reduce((total, column) => total + byStatus[column.status].length, 0),
    [byStatus],
  );

  const loadBoard = useCallback(async () => {
    setLoading(true);
    setError(null);

    try {
      const chunks = await Promise.all(
        BOARD_COLUMNS.map((col) =>
          adminReferenceApi.listOrders({ status: col.status, per_page: 40 }).then((res) => ({
            status: col.status,
            rows: res.data,
          })),
        ),
      );

      const next = createEmptyBoard();
      for (const chunk of chunks) next[chunk.status] = chunk.rows;

      setByStatus(next);
      setLastUpdated(new Date());
    } catch (err: unknown) {
      setError(err instanceof Error ? err.message : 'Kitchen board could not be loaded.');
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    void loadBoard();
  }, [loadBoard]);

  return (
    <div className="admin-page">
      <div className="section-heading section-heading--compact admin-kitchen-heading">
        <div>
          <p className="eyebrow">Kitchen operations</p>
          <h3>Kitchen board</h3>
          <p>
            Live slices of confirmed · preparing · ready. Tap an order for details and transitions.
          </p>
        </div>
        <div className="admin-kitchen-heading__actions">
          <span className="admin-kitchen-heading__meta">{totalVisibleOrders} visible orders</span>
          <span className="admin-kitchen-heading__meta">
            Updated {formatLastUpdated(lastUpdated)}
          </span>
          <button
            type="button"
            className="button button--secondary"
            onClick={() => void loadBoard()}
            disabled={loading}
          >
            {loading ? 'Refreshing…' : 'Refresh board'}
          </button>
        </div>
      </div>

      {error ? (
        <div className="inline-notice inline-notice--error">
          <p>{error}</p>
        </div>
      ) : null}

      {loading ? <p className="admin-muted">Loading stations…</p> : null}

      {!loading && !error ? (
        <div className="admin-kitchen-board">
          {BOARD_COLUMNS.map((col) => {
            const rows = byStatus[col.status] ?? [];

            return (
              <section
                key={col.status}
                className="admin-kitchen-column"
                aria-labelledby={`kitchen-${col.status}`}
              >
                <div className="admin-kitchen-column__head">
                  <div>
                    <h4 id={`kitchen-${col.status}`}>
                      {col.label} <span className="admin-muted">({rows.length})</span>
                    </h4>
                    <p>{col.hint}</p>
                  </div>
                </div>

                <div className="admin-kitchen-column__list">
                  {rows.length === 0 ? (
                    <div className="admin-kitchen-empty">
                      <strong>No orders</strong>
                      <span>This station is clear.</span>
                    </div>
                  ) : (
                    rows.map((row) => (
                      <button
                        key={row.id}
                        type="button"
                        className="admin-order-mini"
                        onClick={() => navigate(`/admin/orders/${encodeURIComponent(row.id)}`)}
                      >
                        <span className="admin-order-mini__topline">
                          <strong>{row.order_code}</strong>
                          <em>{humanizeAdminStatus(row.fulfillment_type)}</em>
                        </span>

                        <span className="admin-muted">
                          {row.item_count} items · {formatCurrency(row.total_amount)}
                        </span>

                        {row.customer_name ? (
                          <span className="admin-muted">Customer: {row.customer_name}</span>
                        ) : null}
                      </button>
                    ))
                  )}
                </div>
              </section>
            );
          })}
        </div>
      ) : null}
    </div>
  );
}
