import { useEffect, useState } from 'react';
import { adminReferenceApi } from '../admin-reference';
import type {
  AdminBranchSummary,
  AdminDeliveryZoneSummary,
  AdminFeeRuleSummary,
  AdminTaxRuleSummary,
} from '../admin-reference/types';
import { AdminEmptyState, AdminErrorState, AdminLoadingState } from './components/AdminStates';
import { SimpleTable } from './components/SimpleTable';
import { StatusBadge } from './components/StatusBadge';

export function AdminSettingsReferencePage() {
  const [busy, setBusy] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [branches, setBranches] = useState<AdminBranchSummary[]>([]);
  const [zones, setZones] = useState<AdminDeliveryZoneSummary[]>([]);
  const [taxes, setTaxes] = useState<AdminTaxRuleSummary[]>([]);
  const [fees, setFees] = useState<AdminFeeRuleSummary[]>([]);

  useEffect(() => {
    let cancelled = false;
    (async () => {
      try {
        const [br, dz, tx, fee] = await Promise.all([
          adminReferenceApi.listBranches({ per_page: 50 }),
          adminReferenceApi.listDeliveryZones({ per_page: 50 }),
          adminReferenceApi.listTaxRules({ per_page: 50 }),
          adminReferenceApi.listFeeRules({ per_page: 50 }),
        ]);
        if (!cancelled) {
          setBranches(br.data);
          setZones(dz.data);
          setTaxes(tx.data);
          setFees(fee.data);
        }
      } catch (err: unknown) {
        if (!cancelled)
          setError(err instanceof Error ? err.message : 'Settings reference could not be loaded.');
      } finally {
        if (!cancelled) setBusy(false);
      }
    })();
    return () => {
      cancelled = true;
    };
  }, []);

  return (
    <div className="admin-page">
      <div className="section-heading section-heading--compact">
        <h3>Settings reference</h3>
        <p>
          Branches, geography, taxation, and service fees mirrored from Laravel configuration
          modules.
        </p>
      </div>

      {busy ? <AdminLoadingState label="Hydrating operational settings…" /> : null}
      {error ? <AdminErrorState title={error} /> : null}

      {!busy && !error ? (
        <>
          <p className="admin-subheading">Branches</p>
          {branches.length > 0 ? (
            <SimpleTable
              headings={['Code', 'Name', 'Currency', 'Delivery', 'Pickup', 'Dine-in', 'Default']}
            >
              {branches.map((b) => (
                <tr key={b.id}>
                  <td>{b.code}</td>
                  <td>{b.name}</td>
                  <td>{b.currency}</td>
                  <td>{b.supports_delivery ? 'yes' : 'no'}</td>
                  <td>{b.supports_pickup ? 'yes' : 'no'}</td>
                  <td>{b.supports_dine_in ? 'yes' : 'no'}</td>
                  <td>{b.is_default ? 'yes' : 'no'}</td>
                </tr>
              ))}
            </SimpleTable>
          ) : (
            <AdminEmptyState
              title="No branches found."
              copy="Seeded demo branches or branch configuration records will appear here."
            />
          )}

          <p className="admin-subheading admin-stack-relaxed">Delivery zones</p>
          {zones.length > 0 ? (
            <SimpleTable headings={['Code', 'Branch', 'Name', 'Pricing', 'Status']}>
              {zones.map((z) => (
                <tr key={z.id}>
                  <td>{z.code}</td>
                  <td>{z.branch_code}</td>
                  <td>{z.name}</td>
                  <td>{z.pricing_strategy}</td>
                  <td>
                    <StatusBadge status={z.status} />
                  </td>
                </tr>
              ))}
            </SimpleTable>
          ) : (
            <AdminEmptyState
              title="No delivery zones found."
              copy="Zone-based delivery pricing rules will appear here once configured."
            />
          )}

          <p className="admin-subheading admin-stack-relaxed">Tax rules</p>
          {taxes.length > 0 ? (
            <SimpleTable headings={['Name', 'Branch', 'Scope', '% rate', 'Active']}>
              {taxes.map((t) => (
                <tr key={t.id}>
                  <td>{t.name}</td>
                  <td>{t.branch_code ?? '—'}</td>
                  <td>{t.fulfillment_type ?? 'any'}</td>
                  <td>
                    {t.percentage_rate != null
                      ? String(t.percentage_rate)
                      : t.rate_bps != null
                        ? `${t.rate_bps} bps`
                        : '—'}
                  </td>
                  <td>{t.is_active ? 'yes' : 'no'}</td>
                </tr>
              ))}
            </SimpleTable>
          ) : (
            <AdminEmptyState
              title="No tax rules found."
              copy="US-first configurable demo tax rules will appear here once seeded or configured."
            />
          )}

          <p className="admin-subheading admin-stack-relaxed">Fee rules</p>
          {fees.length > 0 ? (
            <SimpleTable headings={['Code', 'Kind', 'Branch', 'Type', 'Active']}>
              {fees.map((f) => (
                <tr key={f.id}>
                  <td>{f.code}</td>
                  <td>{f.fee_kind}</td>
                  <td>{f.branch_code ?? '—'}</td>
                  <td>{f.calculation_type}</td>
                  <td>{f.is_active ? 'yes' : 'no'}</td>
                </tr>
              ))}
            </SimpleTable>
          ) : (
            <AdminEmptyState
              title="No fee rules found."
              copy="Service, delivery, and small-order fee configuration will appear here once available."
            />
          )}
        </>
      ) : null}
    </div>
  );
}
