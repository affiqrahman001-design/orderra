import { useCallback, useEffect, useMemo, useState } from 'react';
import { AdminPortal } from '../features/admin-portal/AdminPortal';
import { CustomerAccountPage } from '../features/auth/CustomerAccountPage';
import { CustomerLoginPage } from '../features/auth/CustomerLoginPage';
import { PortalLoginPage } from '../features/auth/PortalLoginPage';
import { canAccessPortal, useAuthStore } from '../features/auth/authStore';
import { DineInJoinPage } from '../pages/DineInJoinPage';
import { HelpCenterPage } from '../pages/HelpCenterPage';
import { PrivacyPolicyPage } from '../pages/PrivacyPolicyPage';
import App from './App';

function readJoinSessionCode(pathname: string): string | null {
  let match = pathname.match(/^\/dine-in\/join\/([^/?#]+)\/?$/i);
  if (!match) {
    match = pathname.match(/^\/qr\/([^/?#]+)\/?$/i);
  }
  const raw = match?.[1]?.trim();
  return raw ? decodeURIComponent(raw) : null;
}

export default function RootLayout() {
  const [pathname, setPathname] = useState(() => window.location.pathname);
  const user = useAuthStore((s) => s.user);
  const loading = useAuthStore((s) => s.loading);
  const hasBooted = useAuthStore((s) => s.hasBooted);
  const boot = useAuthStore((s) => s.boot);

  useEffect(() => {
    const onPop = () => setPathname(window.location.pathname);
    window.addEventListener('popstate', onPop);
    return () => window.removeEventListener('popstate', onPop);
  }, []);

  useEffect(() => {
    void boot();
  }, [boot]);

  const navigateApp = useCallback((next: string, opts?: { replace?: boolean }) => {
    if (opts?.replace) {
      window.history.replaceState({}, '', next);
    } else {
      window.history.pushState({}, '', next);
    }

    setPathname(next);
    window.setTimeout(() => window.scrollTo({ top: 0, left: 0, behavior: 'auto' }), 0);
  }, []);

  useEffect(() => {
    const isPortalLoginPath = pathname === '/portal/login';
    const isLegacyAdminLoginPath =
      pathname === '/admin/login' || pathname.startsWith('/admin/login/');

    if (!isPortalLoginPath && !isLegacyAdminLoginPath) return;
    if (!hasBooted || loading) return;

    if (canAccessPortal(user?.orderra_role)) {
      navigateApp(user?.orderra_role === 'staff' ? '/portal/staff' : '/admin', { replace: true });
      return;
    }

    if (isLegacyAdminLoginPath) {
      navigateApp('/portal/login', { replace: true });
    }
  }, [pathname, hasBooted, loading, user?.orderra_role, navigateApp]);

  const joinSessionCode = useMemo(() => readJoinSessionCode(pathname), [pathname]);

  const goHome = () => {
    window.history.replaceState({}, '', '/');
    setPathname('/');
  };

  if (joinSessionCode && joinSessionCode.length > 0) {
    return <DineInJoinPage sessionCode={joinSessionCode} onGoToMenu={goHome} />;
  }

  if (pathname === '/help-center' || pathname === '/help') {
    return <HelpCenterPage navigate={navigateApp} />;
  }

  if (pathname === '/privacy-policy' || pathname === '/privacy') {
    return <PrivacyPolicyPage navigate={navigateApp} />;
  }

  if (pathname === '/login' || pathname === '/account/login') {
    return <CustomerLoginPage navigate={navigateApp} />;
  }

  if (pathname === '/account' || pathname.startsWith('/account/')) {
    return <CustomerAccountPage navigate={navigateApp} />;
  }

  if (pathname === '/portal/login' || pathname === '/admin/login') {
    if (hasBooted && !loading) {
      if (user?.orderra_role === 'customer') {
        return <CustomerLoginPage navigate={navigateApp} />;
      }
      if (canAccessPortal(user?.orderra_role)) {
        return <AdminPortal pathname="/admin" navigate={navigateApp} />;
      }
    }
    return <PortalLoginPage navigate={navigateApp} />;
  }

  if (pathname === '/portal/staff' || pathname.startsWith('/portal/staff/')) {
    if (loading) return <div className="app-shell app-shell__loading">Restoring session...</div>;
    if (!canAccessPortal(user?.orderra_role)) {
      return <PortalLoginPage navigate={navigateApp} />;
    }
    return <AdminPortal pathname="/admin/orders" navigate={navigateApp} />;
  }

  if (pathname === '/admin' || pathname.startsWith('/admin/')) {
    if (!hasBooted || loading) {
      return <div className="app-shell app-shell__loading">Restoring session...</div>;
    }

    if (!canAccessPortal(user?.orderra_role)) {
      return <PortalLoginPage navigate={navigateApp} />;
    }

    return <AdminPortal pathname={pathname} navigate={navigateApp} />;
  }

  return <App navigate={(next) => navigateApp(next)} />;
}
