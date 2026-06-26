import { useEffect, useState } from "react";
import { QrCodeCard } from "../components/qr/QrCodeCard";
import type { JoinTableQrSummary } from "../services/dineInGuestService";
import { useAppStore } from "../store/appStore";

interface DineInJoinPageProps {
  sessionCode: string;
  onGoToMenu: () => void;
}

export function DineInJoinPage({ sessionCode, onGoToMenu }: DineInJoinPageProps) {
  const joinTableFromQrCode = useAppStore((s) => s.joinTableFromQrCode);

  const [busy, setBusy] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [detail, setDetail] = useState<JoinTableQrSummary | null>(null);

  useEffect(() => {
    let cancelled = false;

    (async () => {
      setBusy(true);
      setError(null);
      setDetail(null);

      try {
        const summary = await joinTableFromQrCode(sessionCode.trim());
        if (!cancelled) setDetail(summary);
      } catch (err) {
        if (!cancelled) {
          setError(err instanceof Error ? err.message : "This dine-in QR code could not be opened.");
        }
      } finally {
        if (!cancelled) setBusy(false);
      }
    })();

    return () => {
      cancelled = true;
    };
  }, [sessionCode, joinTableFromQrCode]);

  return (
    <div className="app-shell dine-in-join-page">
      <main className="container main-layout">
        <section className="checkout-layout dine-in-join-page__shell">
          <div className="checkout-layout__main">
            <div className="section-heading">
              <p className="eyebrow">Dine-in</p>
              <h2>{busy ? "Joining table…" : error ? "Code could not open" : "You are seated"}</h2>
              {!busy && detail ? (
                <p className="dine-in-join-page__hint">
                  {detail.branchName ? `${detail.branchName} · ${detail.sessionCodeDisplay}` : detail.sessionCodeDisplay}
                  {detail.canOrder ?
                    ""
                  : " · Ordering may be paused for this table."}{" "}
                  Your cart will follow this session at checkout when you choose dine-in.
                </p>
              ) : (
                <p>Use the table QR to open ORDERra without typing a manual table code.</p>
              )}
            </div>

            {error ? (
              <div className="inline-notice inline-notice--error">
                <p>{error}</p>
              </div>
            ) : null}

            {!busy && detail ? (
              <QrCodeCard
                value={detail.joinUrl}
                title="Table link"
                subtitle="Scan again or save for your party."
              />
            ) : null}

            <div className="checkout-actions dine-in-join-page__actions">
              <button type="button" className="button button--primary" onClick={onGoToMenu} disabled={busy}>
                {busy ? "Please wait…" : "Continue to menu"}
              </button>
            </div>
          </div>
        </section>
      </main>
    </div>
  );
}
