import { useEffect, useState } from 'react';
import { adminReferenceApi } from '../admin-reference';
import type {
  AdminAuditLogSummary,
  AdminListMeta,
  AdminPaymentLogSummary,
  AdminRefundSummary,
  AdminSupportTicketSummary,
  AdminWebhookSummary,
} from '../admin-reference/types';
import { AdminEmptyState, AdminErrorState, AdminLoadingState } from './components/AdminStates';
import { SimpleTable } from './components/SimpleTable';
import { StatusBadge } from './components/StatusBadge';

type HubKind = 'payments' | 'refunds' | 'webhooks' | 'support' | 'audit' | 'riders' | 'assignments';

function formatMoneyMajor(amount: unknown, currency: unknown): string {
  const dollars = typeof amount === 'number' ? amount : Number(amount);
  if (!Number.isFinite(dollars)) return '—';
  const cur = typeof currency === 'string' ? currency.toUpperCase() : 'USD';
  return `${dollars.toFixed(2)} ${cur}`;
}

export function AdminReferenceHubPage({ kind }: { kind: HubKind }) {
  const [meta, setMeta] = useState<AdminListMeta | null>(null);
  const [rows, setRows] = useState<unknown[]>([]);
  const [busy, setBusy] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [refreshKey, setRefreshKey] = useState(0);
  const [lastUpdatedAt, setLastUpdatedAt] = useState<string | null>(null);

  useEffect(() => {
    let cancelled = false;
    setBusy(true);
    setError(null);

    (async () => {
      try {
        let res: { data: unknown[]; meta: AdminListMeta };

        switch (kind) {
          case 'payments':
            res = await adminReferenceApi.listPayments({ per_page: 25 });
            break;
          case 'refunds':
            res = await adminReferenceApi.listRefunds({ per_page: 25 });
            break;
          case 'webhooks':
            res = await adminReferenceApi.listWebhooks({ per_page: 25 });
            break;
          case 'support':
            res = await adminReferenceApi.listSupportTickets({ per_page: 25 });
            break;
          case 'audit':
            res = await adminReferenceApi.listAuditLogs({ per_page: 25 });
            break;
          case 'riders':
            res = await adminReferenceApi.listRiders({ per_page: 25 });
            break;
          case 'assignments':
            res = await adminReferenceApi.listRiderAssignments({ per_page: 25 });
            break;
          default:
            res = { data: [], meta: { current_page: 1, per_page: 25, total: 0, last_page: 1 } };
        }

        if (!cancelled) {
          setRows(res.data);
          setMeta(res.meta);
          setLastUpdatedAt(
            new Date().toLocaleTimeString([], {
              hour: '2-digit',
              minute: '2-digit',
            }),
          );
        }
      } catch (err: unknown) {
        if (!cancelled) {
          setError(err instanceof Error ? err.message : 'This view could not be loaded.');
        }
      } finally {
        if (!cancelled) setBusy(false);
      }
    })();

    return () => {
      cancelled = true;
    };
  }, [kind, refreshKey]);

  const title =
    kind === 'payments'
      ? 'Payment intents'
      : kind === 'refunds'
        ? 'Refunds'
        : kind === 'webhooks'
          ? 'Webhook events'
          : kind === 'support'
            ? 'Support tickets'
            : kind === 'audit'
              ? 'Audit logs'
              : kind === 'riders'
                ? 'Rider directory'
                : 'Delivery assignments';

  const subtitle =
    kind === 'payments'
      ? 'Latest payment intent attempts from the simulated provider stack.'
      : kind === 'refunds'
        ? 'Portfolio demo refunds and review statuses.'
        : kind === 'webhooks'
          ? 'Ops webhook inbox for integrations (simulated entries only).'
          : kind === 'support'
            ? 'Support tickets surfaced for admin QA.'
            : kind === 'audit'
              ? 'Immutable admin ledger for rotations, transitions, and config edits.'
              : kind === 'riders'
                ? 'Pool of courier personas used for simulated delivery milestones.'
                : 'Assignments tied to outbound delivery orders.';

  return (
    <div className="admin-page">
      <div className="admin-reference-heading">
        <div className="section-heading section-heading--compact">
          <h3>{title}</h3>
          <p>{subtitle}</p>
        </div>

        <div className="admin-reference-heading__actions" aria-label="Reference page actions">
          <span className="admin-reference-heading__meta">
            {lastUpdatedAt ? `Last updated ${lastUpdatedAt}` : 'Not loaded yet'}
          </span>
          <button
            type="button"
            className="button button--secondary admin-reference-heading__button"
            disabled={busy}
            onClick={() => setRefreshKey((value) => value + 1)}
          >
            {busy ? 'Refreshing…' : 'Refresh'}
          </button>
        </div>
      </div>

      {busy ? <AdminLoadingState label={`Loading ${title.toLowerCase()}…`} /> : null}
      {error ? <AdminErrorState title={error} /> : null}

      {!busy && !error && meta && rows.length === 0 ? (
        <AdminEmptyState
          title="Nothing here yet."
          copy="Operate the demo briefly, then revisit this ledger."
        />
      ) : null}

      {!busy && !error && rows.length > 0 ? (
        <>
          <p className="admin-muted">
            Page {meta?.current_page} of {meta?.last_page ?? 1} · {meta?.total ?? rows.length}{' '}
            records indexed.
          </p>
          {kind === 'payments' ? (
            <SimpleTable
              headings={[
                'Code',
                'Status',
                'Method',
                'Provider',
                'Amount',
                'Attempts',
                'Transactions',
              ]}
            >
              {(rows as AdminPaymentLogSummary[]).map((row) => (
                <tr key={row.id}>
                  <td>{row.intent_code}</td>
                  <td>
                    <StatusBadge status={row.status} />
                  </td>
                  <td>{row.method_code}</td>
                  <td>{row.provider_code}</td>
                  <td>{formatMoneyMajor(row.amount, row.currency)}</td>
                  <td>{row.attempts_count}</td>
                  <td>{row.transactions_count}</td>
                </tr>
              ))}
            </SimpleTable>
          ) : null}

          {kind === 'refunds' ? (
            <SimpleTable headings={['Category', 'Status', 'Currency', 'Requested', 'Resolved']}>
              {(rows as AdminRefundSummary[]).map((row) => (
                <tr key={row.id}>
                  <td>{row.category}</td>
                  <td>
                    <StatusBadge status={row.status} />
                  </td>
                  <td>{row.currency}</td>
                  <td>{formatMoneyMajor(row.requested_amount, row.currency)}</td>
                  <td>
                    {row.resolved_amount != null
                      ? formatMoneyMajor(row.resolved_amount, row.currency)
                      : '—'}
                  </td>
                </tr>
              ))}
            </SimpleTable>
          ) : null}

          {kind === 'webhooks' ? (
            <SimpleTable headings={['Event', 'Aggregate', 'Status', 'Replay count', 'Generated']}>
              {(rows as AdminWebhookSummary[]).map((row) => (
                <tr key={row.id}>
                  <td>{row.event_name}</td>
                  <td>{row.aggregate_type}</td>
                  <td>
                    <StatusBadge status={row.status} />
                  </td>
                  <td>{row.replay_count}</td>
                  <td>{row.generated_at ?? '—'}</td>
                </tr>
              ))}
            </SimpleTable>
          ) : null}

          {kind === 'support' ? (
            <SimpleTable headings={['Category', 'Status', 'Subject', 'Opened', 'Resolved']}>
              {(rows as AdminSupportTicketSummary[]).map((row) => (
                <tr key={row.id}>
                  <td>{row.category}</td>
                  <td>
                    <StatusBadge status={row.status} />
                  </td>
                  <td>{row.subject}</td>
                  <td>{row.opened_at ?? '—'}</td>
                  <td>{row.resolved_at ?? '—'}</td>
                </tr>
              ))}
            </SimpleTable>
          ) : null}

          {kind === 'audit' ? (
            <SimpleTable headings={['Action', 'Channel', 'Entity', 'Status', 'Occurred']}>
              {(rows as AdminAuditLogSummary[]).map((row) => (
                <tr key={row.id}>
                  <td>{row.action}</td>
                  <td>{row.channel}</td>
                  <td>
                    {[row.entity_type, row.entity_public_id].filter(Boolean).join(' · ') || '—'}
                  </td>
                  <td>
                    <StatusBadge status={row.status} />
                  </td>
                  <td>{row.occurred_at ?? '—'}</td>
                </tr>
              ))}
            </SimpleTable>
          ) : null}

          {kind === 'riders' ? (
            <SimpleTable headings={['Name', 'Code', 'Type', 'Vehicle', 'Status']}>
              {rows.map((row) => {
                const r = row as Record<string, unknown>;
                const id = String(r.id ?? '');
                return (
                  <tr key={id}>
                    <td>{String(r.name ?? '—')}</td>
                    <td>{String(r.rider_code ?? '—')}</td>
                    <td>{String(r.type ?? '—')}</td>
                    <td>{String(r.vehicle_type ?? '—')}</td>
                    <td>
                      <StatusBadge status={String(r.status ?? 'unknown')} />
                    </td>
                  </tr>
                );
              })}
            </SimpleTable>
          ) : null}

          {kind === 'assignments' ? (
            <SimpleTable headings={['Order', 'Status', 'Rider', 'ETA min', 'Events']}>
              {rows.map((row) => {
                const r = row as Record<string, unknown>;
                const id = String(r.id ?? '');
                const orderRec =
                  typeof r.order === 'object' && r.order !== null
                    ? (r.order as Record<string, unknown>)
                    : null;
                const orderCode =
                  typeof orderRec?.order_code === 'string' ? orderRec.order_code : '—';
                const riderRec =
                  typeof r.rider === 'object' && r.rider !== null
                    ? (r.rider as Record<string, unknown>)
                    : null;
                const riderName = typeof riderRec?.name === 'string' ? riderRec.name : '—';

                return (
                  <tr key={id}>
                    <td>{orderCode}</td>
                    <td>
                      <StatusBadge status={String(r.status ?? 'unknown')} />
                    </td>
                    <td>{riderName}</td>
                    <td>{r.eta_minutes != null ? String(r.eta_minutes) : '—'}</td>
                    <td>{Number(r.tracking_events_count ?? 0)}</td>
                  </tr>
                );
              })}
            </SimpleTable>
          ) : null}
        </>
      ) : null}
    </div>
  );
}
