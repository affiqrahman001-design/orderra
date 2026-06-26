import { useAuthStore } from "./authStore";

export function CustomerAccountPage({ navigate }: { navigate: (path: string, opts?: { replace?: boolean }) => void }) {
  const user = useAuthStore((s) => s.user);
  const logout = useAuthStore((s) => s.logout);

  if (!user || user.orderra_role !== "customer") {
    return (
      <div className="admin-login">
        <div className="form-card admin-login__card">
          <button
            type="button"
            className="auth-card-close"
            aria-label="Back to ORDERra menu"
            onClick={() => navigate("/", { replace: true })}
          >
            ×
          </button>
          <h2>Account sign-in required</h2>
          <p className="admin-muted">Please sign in with a customer account to view this page.</p>
          <button type="button" className="button button--primary" onClick={() => navigate("/login", { replace: true })}>
            Go to sign in
          </button>
        </div>
      </div>
    );
  }

  return (
    <div className="admin-login">
      <div className="form-card admin-login__card">
        <button
          type="button"
          className="auth-card-close"
          aria-label="Back to ORDERra menu"
          onClick={() => navigate("/", { replace: true })}
        >
          ×
        </button>
        <p className="eyebrow">Customer account</p>
        <h2>{user.name}</h2>
        <p className="admin-muted">{user.email}</p>
        <p className="admin-muted">Account features are ready for future order history and support enhancements.</p>
        <div className="checkout-actions admin-login__actions">
          <button
            type="button"
            className="button button--secondary"
            onClick={() => {
              void (async () => {
                await logout();
                navigate("/", { replace: true });
              })();
            }}
          >
            Logout
          </button>
        </div>
      </div>
    </div>
  );
}
