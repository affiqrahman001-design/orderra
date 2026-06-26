import { useCallback, useEffect, useMemo, useState } from 'react';
import { QrCodeCard } from '../../components/qr/QrCodeCard';
import { adminReferenceApi } from '../admin-reference';
import type { AdminRestaurantTableRow } from '../admin-reference/types';
import { AdminEmptyState, AdminErrorState, AdminLoadingState } from './components/AdminStates';
import { StatusBadge } from './components/StatusBadge';

export function AdminDineInQrPage() {
  const [tables, setTables] = useState<AdminRestaurantTableRow[]>([]);
  const [active, setActive] = useState<AdminRestaurantTableRow | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [busy, setBusy] = useState(true);
  const [rotateBusyTableId, setRotateBusyTableId] = useState<string | null>(null);
  const [rotateNotice, setRotateNotice] = useState<string | null>(null);
  const [copyNotice, setCopyNotice] = useState<string | null>(null);

  const reload = useCallback(async () => {
    setBusy(true);
    setError(null);
    setRotateNotice(null);
    setCopyNotice(null);

    try {
      const res = await adminReferenceApi.listRestaurantTables({ per_page: 50 });
      setTables(res.data);
      setActive((prev) => {
        if (!res.data.length) return null;
        if (prev) {
          const next = res.data.find((row) => row.id === prev.id);
          return next ?? res.data[0] ?? null;
        }
        return res.data[0] ?? null;
      });
    } catch (err: unknown) {
      setTables([]);
      setActive(null);
      setError(err instanceof Error ? err.message : 'Tables could not be loaded.');
    } finally {
      setBusy(false);
    }
  }, []);

  useEffect(() => {
    void reload();
  }, [reload]);

  const previewUrl = useMemo(() => {
    const session = active?.active_qr_session;
    if (!session) return '';
    const short = session.public_qr_url?.trim();
    return short || session.join_url || '';
  }, [active]);

  const handleCopy = async (value: string, label: string) => {
    const text = value.trim();
    if (!text) return;

    try {
      if (navigator.clipboard?.writeText) {
        await navigator.clipboard.writeText(text);
      } else {
        const textArea = document.createElement('textarea');
        textArea.value = text;
        textArea.setAttribute('readonly', '');
        textArea.style.position = 'fixed';
        textArea.style.top = '-1000px';
        textArea.style.left = '-1000px';
        document.body.appendChild(textArea);
        textArea.select();

        const copied = document.execCommand('copy');
        document.body.removeChild(textArea);

        if (!copied) {
          throw new Error('Fallback copy failed.');
        }
      }

      setCopyNotice(`${label} copied to clipboard.`);
    } catch {
      setCopyNotice('Could not copy automatically. Select the URL and copy it manually.');
    }
  };

  const handleRotate = async () => {
    if (!active) return;
    setRotateBusyTableId(active.id);
    setRotateNotice(null);
    setCopyNotice(null);
    setError(null);

    try {
      await adminReferenceApi.rotateTableQrSession(active.id, {});
      await reload();
      setRotateNotice('New QR issued. Printed cards must be refreshed.');
    } catch (err: unknown) {
      setError(err instanceof Error ? err.message : 'QR session could not be rotated.');
    } finally {
      setRotateBusyTableId(null);
    }
  };

  return (
    <div className="admin-page admin-dine-qr">
      <div className="section-heading section-heading--compact">
        <h3>Dine-in table QR</h3>
        <p>
          Portfolio-safe sessions per physical table: scan targets the SPA{' '}
          <code>/qr/&lt;code&gt;</code> resolver, carts attach at join, and diners continue with
          dine-in checkout.
        </p>
      </div>

      {rotateNotice ? (
        <div className="inline-notice">
          <p>{rotateNotice}</p>
        </div>
      ) : null}

      {copyNotice ? (
        <div className="inline-notice">
          <p>{copyNotice}</p>
        </div>
      ) : null}

      {error ? <AdminErrorState title={error} /> : null}
      {busy ? <AdminLoadingState label="Loading tables…" /> : null}

      {!busy && !error && tables.length === 0 ? (
        <AdminEmptyState
          title="No active tables seeded."
          copy="Run Laravel seeders locally to hydrate RestaurantTable rows and QR sessions."
        />
      ) : null}

      {!busy && tables.length > 0 ? (
        <div className="admin-dine-qr__split">
          <div className="form-card admin-dine-qr__list">
            <p className="eyebrow">Tables ({tables.length})</p>
            <ul className="admin-dine-qr__ul">
              {tables.map((row) => {
                const session = row.active_qr_session;

                return (
                  <li key={row.id}>
                    <button
                      type="button"
                      className={
                        active?.id === row.id
                          ? 'admin-dine-qr__row is-active'
                          : 'admin-dine-qr__row'
                      }
                      onClick={() => {
                        setActive(row);
                        setCopyNotice(null);
                      }}
                    >
                      <strong>
                        {row.label} ({row.code})
                      </strong>
                      <span>
                        {row.branch?.code ? `${row.branch.code} · ` : ''}
                        {session ? `${session.session_code}` : 'inactive'}
                      </span>
                      <span className="admin-dine-qr__badge-row">
                        {session ? (
                          <StatusBadge status={session.status} />
                        ) : (
                          <small className="admin-muted">no session</small>
                        )}
                      </span>
                    </button>
                  </li>
                );
              })}
            </ul>
          </div>

          <div className="admin-dine-qr__preview">
            {!active ? null : !active.active_qr_session ? (
              <div className="form-card">
                <p className="eyebrow">{active.label}</p>
                <p className="admin-muted">
                  Table is active but has no QR session yet. Rotate to mint a joinable QR for
                  portfolio demos.
                </p>
                <button
                  type="button"
                  className="button button--primary"
                  onClick={() => void handleRotate()}
                >
                  {rotateBusyTableId === active.id ? 'Generating…' : 'Generate QR session'}
                </button>
              </div>
            ) : (
              <>
                <QrCodeCard
                  value={previewUrl || active.active_qr_session.join_url}
                  title="Scan to join table"
                  subtitle={`${active.label} · ${active.active_qr_session.session_code}`}
                  footer={
                    <div className="admin-dine-qr__print admin-no-print">
                      <button
                        type="button"
                        className="button button--secondary"
                        onClick={() => void handleRotate()}
                        disabled={rotateBusyTableId === active.id}
                      >
                        {rotateBusyTableId === active.id ? 'Refreshing…' : 'Regenerate QR'}
                      </button>
                      <button
                        type="button"
                        className="button button--quiet"
                        onClick={() => window.print()}
                      >
                        Print card
                      </button>
                    </div>
                  }
                />

                <div className="form-card admin-dine-qr__urls">
                  <p className="admin-subheading">Customer URLs</p>

                  <div className="admin-dine-qr__url-row">
                    <p className="admin-muted admin-dine-qr__mono">
                      <strong>Primary</strong> {previewUrl || active.active_qr_session.join_url}
                    </p>
                    <button
                      type="button"
                      className="button button--quiet admin-dine-qr__copy-button"
                      onClick={() =>
                        void handleCopy(
                          previewUrl || active.active_qr_session?.join_url || '',
                          'Primary QR link',
                        )
                      }
                    >
                      Copy
                    </button>
                  </div>

                  <div className="admin-dine-qr__url-row">
                    <p className="admin-muted admin-dine-qr__mono">
                      <strong>Classic path</strong> {active.active_qr_session.join_url}
                    </p>
                    <button
                      type="button"
                      className="button button--quiet admin-dine-qr__copy-button"
                      onClick={() =>
                        void handleCopy(active.active_qr_session?.join_url || '', 'Classic QR link')
                      }
                    >
                      Copy
                    </button>
                  </div>

                  {active.branch?.name ? (
                    <p className="admin-muted">
                      Branch <strong>{active.branch.name}</strong>
                    </p>
                  ) : null}

                  {active.active_qr_session.expires_at ? (
                    <p className="admin-muted">
                      TTL hint:&nbsp;{active.active_qr_session.expires_at}
                    </p>
                  ) : null}
                </div>
              </>
            )}
          </div>
        </div>
      ) : null}
    </div>
  );
}
