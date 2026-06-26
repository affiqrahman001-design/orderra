import { useState } from "react";
import { useAdminAuthStore } from "./adminAuthStore";

export type AdminNavigate = (path: string, opts?: { replace?: boolean }) => void;

export function AdminLoginPage({ navigate }: { navigate: AdminNavigate }) {
  const login = useAdminAuthStore((s) => s.login);
  const loading = useAdminAuthStore((s) => s.loading);
  const authError = useAdminAuthStore((s) => s.authError);
  const clearAuthError = useAdminAuthStore((s) => s.clearAuthError);

  const [email, setEmail] = useState("");
  const [password, setPassword] = useState("");

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
        <div className="section-heading">
          <p className="eyebrow">ORDERra Restaurant Portal</p>
          <h2>Staff and manager access</h2>
          <p>Use the seeded demo accounts for operations access in this demo build.</p>
          <p className="admin-muted">Admin: admin@orderra.test / password</p>
          <p className="admin-muted">Staff: staff@orderra.test / password</p>
        </div>

        <form
          className="form-grid"
          onSubmit={(event) => {
            event.preventDefault();
            clearAuthError();
            void (async () => {
              try {
                await login(email.trim(), password);
                navigate("/admin", { replace: true });
              } catch {
                /* error surfaced inline */
              }
            })();
          }}
        >
          <label className="field">
            <span>Email</span>
            <input
              autoComplete="username"
              type="email"
              value={email}
              onChange={(event) => setEmail(event.target.value)}
              placeholder="staff@orderra.test"
            />
          </label>
          <label className="field">
            <span>Password</span>
            <input
              autoComplete="current-password"
              type="password"
              value={password}
              onChange={(event) => setPassword(event.target.value)}
            />
          </label>
          {authError ? (
            <div className="inline-notice inline-notice--error">
              <p>{authError}</p>
            </div>
          ) : null}
          <div className="checkout-actions admin-login__actions">
            <button type="submit" className="button button--primary" disabled={loading}>
              {loading ? "Signing in…" : "Sign in"}
            </button>
          </div>
        </form>
      </div>
    </div>
  );
}
