import { useEffect, useMemo, useState } from 'react';
import { adminReferenceApi } from '../admin-reference';
import type { AdminDashboardResponse } from '../admin-reference/types';
import type { AdminNavigate } from './AdminLoginPage';
import { StatCard } from './components/StatCard';

type DashboardQuickAction = {
  label: string;
  description: string;
  path: string;
  metric?: string;
  adminOnly?: boolean;
};

function formatDashboardLabel(value: string): string {
  return value
    .replace(/_/g, ' ')
    .replace(/\b\w/g, (letter) => letter.toUpperCase())
    .replace(/Qr/g, 'QR');
}

export function AdminDashboardPage({
  navigate,
  role,
}: {
  navigate: AdminNavigate;
  role?: string | null;
}) {
  const [data, setData] = useState<AdminDashboardResponse['data'] | null>(null);
  const [error, setError] = useState<string | null>(null);

  const isAdmin = role === 'admin';

  const quickActions = useMemo<DashboardQuickAction[]>(
    () =>
      [
        {
          label: 'Review orders',
          description: 'Open the paginated order list, filters, and order detail flow.',
          path: '/admin/orders',
          metric: `${data?.counters?.orders_active ?? data?.counters?.total_orders ?? 0} active`,
        },
        {
          label: 'Open kitchen board',
          description: 'Track confirmed, preparing, and ready orders by station.',
          path: '/admin/kitchen',
          metric: `${data?.counters?.pending_orders ?? 0} pending`,
        },
        {
          label: 'Manage table QR',
          description: 'Preview dine-in QR sessions and rotate table links safely.',
          path: '/admin/dine-in-qr',
          metric: `${data?.counters?.qr_sessions_active ?? 0} active`,
        },
        {
          label: 'Support tickets',
          description: 'Review customer support items and operational follow-ups.',
          path: '/admin/support',
          metric: `${data?.counters?.support_attention ?? data?.counters?.support_ticket_count ?? 0} attention`,
        },
        {
          label: 'Payment logs',
          description: 'Inspect demo-safe payment intents, attempts, and transactions.',
          path: '/admin/payments',
          metric: `${data?.counters?.payments_pending ?? 0} pending`,
          adminOnly: true,
        },
        {
          label: 'Run simulator',
          description: 'Open payment, rider, refund, and webhook demo controls.',
          path: '/admin/simulator',
          metric: 'Demo only',
          adminOnly: true,
        },
      ].filter((item) => !item.adminOnly || isAdmin),
    [data, isAdmin],
  );

  useEffect(() => {
    let cancelled = false;
    setError(null);

    adminReferenceApi
      .getDashboard()
      .then((res) => {
        if (!cancelled) setData(res.data);
      })
      .catch((err: unknown) => {
        if (!cancelled) {
          setError(err instanceof Error ? err.message : 'Dashboard could not be loaded.');
        }
      });

    return () => {
      cancelled = true;
    };
  }, []);

  return (
    <div className="admin-page">
      <div className="section-heading section-heading--compact">
        <p className="eyebrow">Operations overview</p>
        <h3>Dashboard</h3>
        <p>
          Live backend aggregates with quick links for the main staff workflows. Demo-safe totals
          only.
        </p>
      </div>

      {error ? (
        <div className="inline-notice inline-notice--error">
          <p>{error}</p>
        </div>
      ) : null}

      {!data && !error ? (
        <div className="form-card admin-dashboard-skeleton" aria-live="polite">
          <p className="admin-muted">Loading dashboard metrics…</p>
        </div>
      ) : null}

      {data ? (
        <>
          <div className="admin-dashboard-hero">
            <div className="admin-dashboard-hero__copy">
              <p className="eyebrow">Today at a glance</p>
              <h4>Keep the restaurant flow moving.</h4>
              <p>
                Start with orders or kitchen during service. Use reference pages for demo-safe
                payment, rider, support, and audit visibility.
              </p>
            </div>
            <div className="admin-dashboard-hero__meta">
              <span>{data.recent_orders?.length ?? 0} recent orders</span>
              <span>{data.recent_payments?.length ?? 0} recent payments</span>
              <span>{data.recent_webhooks?.length ?? 0} webhook events</span>
            </div>
          </div>

          <div className="admin-quick-actions" aria-label="Admin quick actions">
            {quickActions.map((item) => (
              <button
                key={item.path}
                type="button"
                className="admin-quick-action-card"
                onClick={() => navigate(item.path)}
              >
                <span className="admin-quick-action-card__topline">
                  <strong>{item.label}</strong>
                  {item.metric ? <em>{item.metric}</em> : null}
                </span>
                <span>{item.description}</span>
              </button>
            ))}
          </div>

          <div className="form-card admin-dash-grid">
            {Object.entries(data.counters ?? {}).map(([key, value]) => (
              <StatCard key={key} label={formatDashboardLabel(key)} value={String(value)} />
            ))}
          </div>

          <div className="inline-notice">
            <p>
              Recent orders: {data.recent_orders?.length ?? 0} · Recent payments:{' '}
              {data.recent_payments?.length ?? 0} · Use Orders for the paginated list and Kitchen
              for station columns.
            </p>
          </div>
        </>
      ) : null}
    </div>
  );
}
