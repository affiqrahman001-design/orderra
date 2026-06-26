import { useCallback, useEffect, useMemo, useState } from 'react';
import { MenuThumbnail } from '../../components/MenuThumbnail';
import { ApiError } from '../../lib/api/client';
import { formatCurrency } from '../../lib/currency';
import { resolveMenuImage } from '../../lib/menuAssets';
import { adminReferenceApi } from '../admin-reference';
import type { AdminOrderDetail } from '../admin-reference/types';
import type { AdminNavigate } from './AdminLoginPage';
import { humanizeAdminStatus } from './adminOrdersConstants';
import { StatusBadge } from './components/StatusBadge';
import { unwrapAdminOrderEnvelope } from './unwrapAdminOrder';

function summarizeError(err: unknown, fallback: string): string {
  if (err instanceof ApiError) {
    const payload = err.payload;
    if (payload && typeof payload === 'object') {
      const record = payload as Record<string, unknown>;
      if (typeof record.message === 'string' && record.message.trim()) return record.message;
    }
    return err.message || fallback;
  }
  if (err instanceof Error && err.message) return err.message;
  return fallback;
}

function formatDateTime(value?: string | null): string {
  if (!value) return '—';
  const parsed = new Date(value);
  if (Number.isNaN(parsed.getTime())) return value;
  return parsed.toLocaleString();
}

export function AdminOrderDetailPage({
  orderId,
  navigate,
}: {
  orderId: string;
  navigate: AdminNavigate;
}) {
  const [order, setOrder] = useState<AdminOrderDetail | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [notice, setNotice] = useState<string | null>(null);
  const [loading, setLoading] = useState(true);
  const [movingTo, setMovingTo] = useState<string | null>(null);
  const [lastLoadedAt, setLastLoadedAt] = useState<Date | null>(null);

  const decodedId = useMemo(() => decodeURIComponent(orderId), [orderId]);

  const loadOrder = useCallback(async () => {
    setLoading(true);
    setError(null);
    setNotice(null);

    try {
      const raw = await adminReferenceApi.getOrder(decodedId);
      setOrder(unwrapAdminOrderEnvelope(raw));
      setLastLoadedAt(new Date());
    } catch (err: unknown) {
      setOrder(null);
      setError(summarizeError(err, 'Order could not be loaded.'));
    } finally {
      setLoading(false);
    }
  }, [decodedId]);

  useEffect(() => {
    let cancelled = false;
    setLoading(true);
    setError(null);
    setNotice(null);

    adminReferenceApi
      .getOrder(decodedId)
      .then((raw) => {
        if (!cancelled) {
          setOrder(unwrapAdminOrderEnvelope(raw));
          setLastLoadedAt(new Date());
        }
      })
      .catch((err: unknown) => {
        if (!cancelled) {
          setOrder(null);
          setError(summarizeError(err, 'Order could not be loaded.'));
        }
      })
      .finally(() => {
        if (!cancelled) setLoading(false);
      });

    return () => {
      cancelled = true;
    };
  }, [decodedId]);

  async function transitionTo(to_status: string) {
    try {
      setMovingTo(to_status);
      setError(null);
      setNotice(null);

      const raw = await adminReferenceApi.transitionOrderStatus(decodedId, { to_status });
      const next = unwrapAdminOrderEnvelope(raw);

      setOrder(next);
      setLastLoadedAt(new Date());
      setNotice(`Order moved to ${humanizeAdminStatus(next.status)}.`);
    } catch (err: unknown) {
      setError(summarizeError(err, 'Transition failed.'));
    } finally {
      setMovingTo(null);
    }
  }

  const customerName =
    typeof order?.customer_context?.name === 'string' ? order.customer_context.name : null;

  const lastLoadedLabel = lastLoadedAt
    ? lastLoadedAt.toLocaleTimeString([], {
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
      })
    : 'Not loaded yet';

  return (
    <div className="admin-page admin-order-detail">
      <div className="section-heading section-heading--compact admin-order-detail__head">
        <div>
          <button
            type="button"
            className="button button--quiet"
            onClick={() => navigate('/admin/orders')}
          >
            ← Back to orders
          </button>
          <h3 className="admin-order-detail__title">{order?.order_code ?? 'Order'}</h3>
          <p className="admin-muted">
            {loading
              ? 'Loading…'
              : order
                ? `${humanizeAdminStatus(order.status)} · ${humanizeAdminStatus(order.fulfillment_type)}`
                : ''}
          </p>
        </div>

        <div className="admin-order-detail__head-actions">
          {order ? <StatusBadge status={order.status} /> : null}
          <span className="admin-order-detail__loaded">Updated {lastLoadedLabel}</span>
          <button
            type="button"
            className="button button--secondary"
            onClick={() => void loadOrder()}
            disabled={loading}
          >
            {loading ? 'Refreshing…' : 'Refresh order'}
          </button>
        </div>
      </div>

      {notice ? (
        <div className="inline-notice">
          <p>{notice}</p>
        </div>
      ) : null}

      {error ? (
        <div className="inline-notice inline-notice--error">
          <p>{error}</p>
        </div>
      ) : null}

      {!loading && !order && !error ? <p className="admin-muted">Order not found.</p> : null}

      {order ? (
        <>
          <div className="form-card admin-order-detail__grid">
            <div>
              <h4 className="admin-subheading">Totals</h4>
              <dl className="admin-dl">
                <div>
                  <dt>Subtotal</dt>
                  <dd>{formatCurrency(order.totals.subtotal)}</dd>
                </div>
                <div>
                  <dt>Discount</dt>
                  <dd>−{formatCurrency(order.totals.discount)}</dd>
                </div>
                <div>
                  <dt>Service</dt>
                  <dd>{formatCurrency(order.totals.service_fee)}</dd>
                </div>
                <div>
                  <dt>Delivery</dt>
                  <dd>{formatCurrency(order.totals.delivery_fee)}</dd>
                </div>
                {typeof order.totals.tax === 'number' ? (
                  <div>
                    <dt>Tax</dt>
                    <dd>{formatCurrency(order.totals.tax)}</dd>
                  </div>
                ) : null}
                {typeof order.totals.tip === 'number' ? (
                  <div>
                    <dt>Tip</dt>
                    <dd>{formatCurrency(order.totals.tip)}</dd>
                  </div>
                ) : null}
                <div>
                  <dt>Total</dt>
                  <dd>
                    <strong>{formatCurrency(order.totals.total)}</strong>
                  </dd>
                </div>
              </dl>
            </div>

            <div>
              <h4 className="admin-subheading">Customer</h4>
              <p>{customerName?.trim() || '—'}</p>
              <p className="admin-muted admin-order-detail__meta">
                Placed {formatDateTime(order.placed_at)}
              </p>
            </div>

            {order.fulfillment ? (
              <div>
                <h4 className="admin-subheading">Fulfillment</h4>
                <dl className="admin-dl">
                  <div>
                    <dt>Mode</dt>
                    <dd>{humanizeAdminStatus(order.fulfillment_type)}</dd>
                  </div>
                  {'contact_name' in order.fulfillment && order.fulfillment.contact_name ? (
                    <div>
                      <dt>Contact</dt>
                      <dd>{String(order.fulfillment.contact_name)}</dd>
                    </div>
                  ) : null}
                  {'table_label' in order.fulfillment && order.fulfillment.table_label ? (
                    <div>
                      <dt>Table</dt>
                      <dd>{String(order.fulfillment.table_label)}</dd>
                    </div>
                  ) : null}
                  {'pickup_code' in order.fulfillment && order.fulfillment.pickup_code ? (
                    <div>
                      <dt>Pickup code</dt>
                      <dd>{String(order.fulfillment.pickup_code)}</dd>
                    </div>
                  ) : null}
                </dl>
              </div>
            ) : null}
          </div>

          <div className="form-card admin-order-transition-card">
            <div className="admin-order-transition-card__head">
              <div>
                <h4 className="admin-subheading">Move status</h4>
                <p className="admin-muted">Current status: {humanizeAdminStatus(order.status)}</p>
              </div>
              <StatusBadge status={order.status} />
            </div>

            {order.allowed_transitions.length > 0 ? (
              <div className="admin-order-actions">
                {order.allowed_transitions.map((to) => (
                  <button
                    key={to}
                    type="button"
                    className="button button--secondary"
                    disabled={movingTo !== null}
                    onClick={() => void transitionTo(to)}
                  >
                    {movingTo === to ? 'Updating…' : `Move to ${humanizeAdminStatus(to)}`}
                  </button>
                ))}
              </div>
            ) : (
              <div className="admin-order-transition-card__empty">
                <strong>No further transitions available.</strong>
                <span>This order is already at a closed or waiting state.</span>
              </div>
            )}
          </div>

          <div className="section-heading section-heading--compact">
            <h4>Line items</h4>
          </div>

          <ul className="admin-order-lines">
            {order.items.map((item) => {
              const slug = typeof item.item_slug === 'string' ? item.item_slug : undefined;
              const img = resolveMenuImage(slug ?? null, item.image_url ?? null);
              const lineTotal =
                typeof item.line_subtotal === 'number'
                  ? item.line_subtotal
                  : Math.round(item.unit_price * item.quantity * 100) / 100;

              return (
                <li key={String(item.id)} className="admin-order-lines__row">
                  <MenuThumbnail
                    src={img}
                    alt={item.item_name}
                    className="admin-order-lines__thumb"
                  />
                  <div className="admin-order-lines__body">
                    <strong>{item.item_name}</strong>
                    {item.note ? <p className="admin-muted">{item.note}</p> : null}
                    <p className="admin-muted">
                      {item.quantity} × {formatCurrency(item.unit_price)} ·{' '}
                      {formatCurrency(lineTotal)}
                    </p>
                  </div>
                </li>
              );
            })}
          </ul>

          {order.status_history && order.status_history.length > 0 ? (
            <>
              <div className="section-heading section-heading--compact">
                <h4>Status history</h4>
              </div>

              <ul className="admin-timeline">
                {order.status_history.map((entry) => {
                  const row = entry as Record<string, unknown>;
                  const to = typeof row.to_status === 'string' ? row.to_status : '';
                  const from = typeof row.from_status === 'string' ? row.from_status : '';
                  const created = typeof row.created_at === 'string' ? row.created_at : '';
                  const reason = typeof row.reason === 'string' ? row.reason : '';

                  return (
                    <li
                      key={String(row.id ?? `${from}-${to}-${created}`)}
                      className="admin-timeline__item"
                    >
                      <strong>{humanizeAdminStatus(to || 'updated')}</strong>
                      <span className="admin-muted">
                        {' '}
                        {from ? `from ${humanizeAdminStatus(from)}` : ''}
                        {created ? ` · ${formatDateTime(created)}` : ''}
                      </span>
                      {reason ? <p className="admin-muted">{reason}</p> : null}
                    </li>
                  );
                })}
              </ul>
            </>
          ) : null}
        </>
      ) : null}
    </div>
  );
}
