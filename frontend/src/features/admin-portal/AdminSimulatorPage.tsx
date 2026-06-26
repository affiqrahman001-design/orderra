import { useEffect, useState } from 'react';
import { adminReferenceApi } from '../admin-reference';
import type { AdminDemoScenariosResponse } from '../admin-reference/types';
import { AdminEmptyState, AdminErrorState, AdminLoadingState } from './components/AdminStates';
import { SimpleTable } from './components/SimpleTable';

type SimulatorData = AdminDemoScenariosResponse['data'];

function formatKey(key: string): string {
  return key.replace(/_/g, ' ');
}

function guardStatusLabel(key: string, value: boolean): string {
  if (key === 'payments_demo_mode') return value ? 'Demo mode on' : 'Demo mode off';
  if (key === 'payments_block_live_execution')
    return value ? 'Live execution blocked' : 'Live execution allowed';
  if (key === 'payments_allow_webhook_simulation')
    return value ? 'Demo webhooks on' : 'Demo webhooks off';
  if (key === 'ops_replay_enabled') return value ? 'Replay demo on' : 'Replay demo off';

  return value ? 'Enabled' : 'Disabled';
}

function isGuardSafe(key: string, value: boolean): boolean {
  if (key === 'payments_demo_mode') return value;
  if (key === 'payments_block_live_execution') return value;
  if (key.includes('live')) return !value;

  return true;
}

function formatRuleValues(values: string[]): string {
  return values.length > 0 ? values.join(', ') : 'Not configured';
}

export function AdminSimulatorPage() {
  const [data, setData] = useState<SimulatorData | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [busy, setBusy] = useState(true);

  useEffect(() => {
    let cancelled = false;
    adminReferenceApi
      .getDemoScenarios()
      .then((res) => {
        if (!cancelled) setData(res.data);
      })
      .catch((err: unknown) => {
        if (!cancelled)
          setError(err instanceof Error ? err.message : 'Simulator guide could not load.');
      })
      .finally(() => {
        if (!cancelled) setBusy(false);
      });
    return () => {
      cancelled = true;
    };
  }, []);

  const guardEntries = data?.guards ? Object.entries(data.guards) : [];
  const ruleEntries = data?.simulation_rules ? Object.entries(data.simulation_rules) : [];

  return (
    <div className="admin-page">
      <div className="section-heading section-heading--compact">
        <h3>Simulation &amp; webhooks</h3>
        <p>
          Reference map of demo-only routes. Nothing here captures live cards or moves real
          money—use it to narrate the demo in portfolio walkthroughs.
        </p>
      </div>

      <div className="inline-notice admin-simulator-safe-notice">
        <p>
          <strong>Demo-safe simulator only.</strong> These controls are for sandbox-style
          storytelling, QA, and portfolio walkthroughs. They must not capture live payments, trigger
          payouts, or call real payment providers.
        </p>
      </div>

      {busy ? <AdminLoadingState label="Loading simulation catalog…" /> : null}
      {error ? <AdminErrorState title={error} /> : null}

      {!busy && !error && data ? (
        <>
          <section className="form-card admin-simulator-panel">
            <p className="admin-subheading">Safety guards</p>
            {guardEntries.length > 0 ? (
              <div className="admin-simulator-guard-grid">
                {guardEntries.map(([key, value]) => {
                  const safe = isGuardSafe(key, value);

                  return (
                    <article className="admin-simulator-guard" key={key}>
                      <span className="admin-simulator-guard__label">{formatKey(key)}</span>
                      <strong
                        className={`admin-simulator-guard__pill ${safe ? 'is-safe' : 'is-risk'}`}
                      >
                        {guardStatusLabel(key, value)}
                      </strong>
                    </article>
                  );
                })}
              </div>
            ) : (
              <AdminEmptyState
                title="No guard flags found."
                copy="Demo safety flags from backend config will appear here once available."
              />
            )}
          </section>

          <section className="form-card admin-simulator-panel admin-stack-tight">
            <p className="admin-subheading">Allowed simulation rules</p>
            {ruleEntries.length > 0 ? (
              <div className="admin-simulator-rule-grid">
                {ruleEntries.map(([key, values]) => (
                  <article className="admin-simulator-rule" key={key}>
                    <strong>{formatKey(key)}</strong>
                    <p>{formatRuleValues(values)}</p>
                  </article>
                ))}
              </div>
            ) : (
              <AdminEmptyState
                title="No simulation rules found."
                copy="Allowed demo outcomes, webhook events, and rider flow rules will appear here."
              />
            )}
          </section>

          <section className="form-card admin-stack-tight">
            <p className="admin-subheading">Callable scenarios</p>
            {data.scenarios && data.scenarios.length > 0 ? (
              <SimpleTable headings={['Label', 'Method', 'Path', 'Notes']}>
                {data.scenarios.map((scenario) => (
                  <tr key={scenario.key}>
                    <td>
                      <strong>{scenario.label}</strong>
                      <div className="admin-muted">{scenario.key}</div>
                    </td>
                    <td>{scenario.method}</td>
                    <td>{scenario.path}</td>
                    <td>{scenario.notes}</td>
                  </tr>
                ))}
              </SimpleTable>
            ) : (
              <AdminEmptyState
                title="No callable scenarios found."
                copy="Demo routes from backend admin config will appear here once configured."
              />
            )}
          </section>
        </>
      ) : null}
    </div>
  );
}
