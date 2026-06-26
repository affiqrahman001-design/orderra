import { useEffect, useMemo, useRef } from 'react';
import { useAuthStore } from '../auth/authStore';
import { AdminDashboardPage } from './AdminDashboardPage';
import { AdminDineInQrPage } from './AdminDineInQrPage';
import { AdminKitchenPage } from './AdminKitchenPage';
import { AdminLoginPage, type AdminNavigate } from './AdminLoginPage';
import { AdminOrderDetailPage } from './AdminOrderDetailPage';
import { AdminOrdersPage } from './AdminOrdersPage';
import { AdminReferenceHubPage } from './AdminReferenceHubPage';
import { AdminSettingsReferencePage } from './AdminSettingsReferencePage';
import { AdminSimulatorPage } from './AdminSimulatorPage';
import { useAdminAuthStore } from './adminAuthStore';

type ResolvedAdminView =
  | { kind: 'login' }
  | { kind: 'dashboard' }
  | { kind: 'dine-in-qr' }
  | { kind: 'orders' }
  | { kind: 'order-detail'; orderId: string }
  | { kind: 'kitchen' }
  | { kind: 'payments' }
  | { kind: 'refunds' }
  | { kind: 'webhooks' }
  | { kind: 'support' }
  | { kind: 'audit' }
  | { kind: 'riders' }
  | { kind: 'assignments' }
  | { kind: 'simulator' }
  | { kind: 'settings-ref' };

function resolveAdminView(pathname: string): ResolvedAdminView {
  const p = pathname.replace(/\/+$/, '') || '/';

  if (p === '/admin/login' || p.startsWith('/admin/login/')) return { kind: 'login' };
  if (p.startsWith('/admin/dine-in-qr')) return { kind: 'dine-in-qr' };
  if (p.startsWith('/admin/kitchen')) return { kind: 'kitchen' };

  if (p.startsWith('/admin/payments')) return { kind: 'payments' };
  if (p.startsWith('/admin/refunds')) return { kind: 'refunds' };
  if (p.startsWith('/admin/webhooks')) return { kind: 'webhooks' };
  if (p.startsWith('/admin/support')) return { kind: 'support' };
  if (p.startsWith('/admin/audit')) return { kind: 'audit' };
  if (p.startsWith('/admin/riders')) return { kind: 'riders' };
  if (p.startsWith('/admin/assignments')) return { kind: 'assignments' };
  if (p.startsWith('/admin/simulator')) return { kind: 'simulator' };
  if (p.startsWith('/admin/settings-reference')) return { kind: 'settings-ref' };

  if (p === '/admin/orders') return { kind: 'orders' };
  const ordersMatch = p.match(/^\/admin\/orders\/([^/?#]+)$/);
  if (ordersMatch?.[1]) return { kind: 'order-detail', orderId: ordersMatch[1] };

  return { kind: 'dashboard' };
}

type AdminNavItem = {
  label: string;
  path: string;
  active: boolean;
  adminOnly?: boolean;
};

function adminNavClass(active: boolean, variant: 'primary' | 'reference' = 'primary'): string {
  return [
    'button',
    variant === 'primary' ? 'button--quiet' : 'button--secondary',
    'admin-shell__nav-button',
    active ? 'admin-shell__nav-button--active' : '',
  ]
    .filter(Boolean)
    .join(' ');
}

export function AdminPortal({ pathname, navigate }: { pathname: string; navigate: AdminNavigate }) {
  const view = useMemo(() => resolveAdminView(pathname), [pathname]);
  const token = useAdminAuthStore((s) => s.token);
  const user = useAdminAuthStore((s) => s.user);
  const loading = useAdminAuthStore((s) => s.loading);
  const boot = useAdminAuthStore((s) => s.boot);
  const logout = useAdminAuthStore((s) => s.logout);
  const authLoading = useAuthStore((s) => s.loading);
  const hasBooted = useAuthStore((s) => s.hasBooted);
  const isAdmin = user?.role === 'admin';
  const isStaff = user?.role === 'staff';
  const activeNavButtonRef = useRef<HTMLButtonElement | null>(null);

  const primaryNavItems: AdminNavItem[] = [
    { label: 'Dashboard', path: '/admin', active: view.kind === 'dashboard' },
    {
      label: 'Orders',
      path: '/admin/orders',
      active: view.kind === 'orders' || view.kind === 'order-detail',
    },
    { label: 'Kitchen', path: '/admin/kitchen', active: view.kind === 'kitchen' },
    { label: 'Table QR', path: '/admin/dine-in-qr', active: view.kind === 'dine-in-qr' },
    {
      label: 'Simulator',
      path: '/admin/simulator',
      active: view.kind === 'simulator',
      adminOnly: true,
    },
  ].filter((item) => !item.adminOnly || isAdmin);

  const referenceNavItems: AdminNavItem[] = [
    {
      label: 'Payments',
      path: '/admin/payments',
      active: view.kind === 'payments',
      adminOnly: true,
    },
    { label: 'Refunds', path: '/admin/refunds', active: view.kind === 'refunds', adminOnly: true },
    {
      label: 'Webhooks',
      path: '/admin/webhooks',
      active: view.kind === 'webhooks',
      adminOnly: true,
    },
    { label: 'Support', path: '/admin/support', active: view.kind === 'support' },
    { label: 'Riders', path: '/admin/riders', active: view.kind === 'riders' },
    { label: 'Assignments', path: '/admin/assignments', active: view.kind === 'assignments' },
    { label: 'Audit', path: '/admin/audit', active: view.kind === 'audit', adminOnly: true },
    {
      label: 'Settings ref',
      path: '/admin/settings-reference',
      active: view.kind === 'settings-ref',
      adminOnly: true,
    },
  ].filter((item) => !item.adminOnly || isAdmin);

  useEffect(() => {
    void boot();
  }, [boot]);

  useEffect(() => {
    const activeButton = activeNavButtonRef.current;
    if (!activeButton) return;

    const frame = window.requestAnimationFrame(() => {
      activeButton.scrollIntoView({
        behavior: 'smooth',
        block: 'nearest',
        inline: 'center',
      });
    });

    return () => window.cancelAnimationFrame(frame);
  }, [view.kind, isAdmin, isStaff]);

  useEffect(() => {
    if (loading) return;
    if (!hasBooted || authLoading) return;

    if (view.kind === 'login') {
      if (token && user) navigate('/admin', { replace: true });
      return;
    }

    if (!token || !user) {
      navigate('/portal/login', { replace: true });
    }
  }, [view, token, user, loading, hasBooted, authLoading, navigate]);

  return (
    <div className="app-shell admin-shell">
      {view.kind === 'login' ? (
        <AdminLoginPage navigate={navigate} />
      ) : (
        <>
          <header className="admin-shell__header admin-no-print">
            <div className="container admin-shell__header-inner">
              <div>
                <p className="eyebrow">ORDERra</p>
                <strong className="admin-shell__title">Operations</strong>
                {user ? (
                  <p className="admin-shell__user">
                    {user.name} · {user.role}
                  </p>
                ) : null}
              </div>
              <div className="admin-shell__nav-wrap">
                <nav className="admin-shell__nav" aria-label="Admin primary sections">
                  {primaryNavItems.map((item) => (
                    <button
                      key={item.path}
                      type="button"
                      ref={item.active ? activeNavButtonRef : undefined}
                      className={adminNavClass(item.active)}
                      aria-current={item.active ? 'page' : undefined}
                      onClick={() => navigate(item.path)}
                    >
                      {item.label}
                    </button>
                  ))}
                  <button
                    type="button"
                    className="button button--secondary admin-shell__nav-button"
                    onClick={() => {
                      void (async () => {
                        await logout();
                        navigate('/portal/login', { replace: true });
                      })();
                    }}
                  >
                    Log out
                  </button>
                </nav>
                <nav className="admin-shell__reference-nav" aria-label="Admin reference sections">
                  <span className="admin-shell__reference-label">Reference</span>
                  {referenceNavItems.map((item) => (
                    <button
                      key={item.path}
                      type="button"
                      ref={item.active ? activeNavButtonRef : undefined}
                      className={adminNavClass(item.active, 'reference')}
                      aria-current={item.active ? 'page' : undefined}
                      onClick={() => navigate(item.path)}
                    >
                      {item.label}
                    </button>
                  ))}
                </nav>
              </div>
            </div>
          </header>

          <main className="container main-layout admin-shell__main">
            {loading && !user ? <p className="admin-muted">Restoring session…</p> : null}
            {!loading && user && view.kind === 'dashboard' ? (
              <AdminDashboardPage navigate={navigate} role={user.role} />
            ) : null}
            {!loading && user && view.kind === 'dine-in-qr' ? <AdminDineInQrPage /> : null}
            {!loading && user && view.kind === 'orders' ? (
              <AdminOrdersPage navigate={navigate} />
            ) : null}
            {!loading && user && view.kind === 'order-detail' ? (
              <AdminOrderDetailPage orderId={view.orderId} navigate={navigate} />
            ) : null}
            {!loading && user && view.kind === 'kitchen' ? (
              <AdminKitchenPage navigate={navigate} />
            ) : null}
            {!loading && user && isAdmin && view.kind === 'payments' ? (
              <AdminReferenceHubPage kind="payments" />
            ) : null}
            {!loading && user && isAdmin && view.kind === 'refunds' ? (
              <AdminReferenceHubPage kind="refunds" />
            ) : null}
            {!loading && user && isAdmin && view.kind === 'webhooks' ? (
              <AdminReferenceHubPage kind="webhooks" />
            ) : null}
            {!loading && user && view.kind === 'support' ? (
              <AdminReferenceHubPage kind="support" />
            ) : null}
            {!loading && user && isAdmin && view.kind === 'audit' ? (
              <AdminReferenceHubPage kind="audit" />
            ) : null}
            {!loading && user && view.kind === 'riders' ? (
              <AdminReferenceHubPage kind="riders" />
            ) : null}
            {!loading && user && view.kind === 'assignments' ? (
              <AdminReferenceHubPage kind="assignments" />
            ) : null}
            {!loading && user && isAdmin && view.kind === 'simulator' ? (
              <AdminSimulatorPage />
            ) : null}
            {!loading && user && isAdmin && view.kind === 'settings-ref' ? (
              <AdminSettingsReferencePage />
            ) : null}
            {!loading &&
            user &&
            isStaff &&
            ['payments', 'refunds', 'webhooks', 'audit', 'simulator', 'settings-ref'].includes(
              view.kind,
            ) ? (
              <div className="inline-notice inline-notice--error admin-restricted-notice">
                <div>
                  <p>
                    <strong>Admin-only reference area.</strong> This section is restricted to admin
                    accounts in demo operations. Staff can continue with orders, kitchen, table QR,
                    support, riders, and assignments.
                  </p>
                </div>
                <button
                  type="button"
                  className="button button--secondary admin-restricted-notice__action"
                  onClick={() => navigate('/admin')}
                >
                  Back to dashboard
                </button>
              </div>
            ) : null}
          </main>
        </>
      )}
    </div>
  );
}
