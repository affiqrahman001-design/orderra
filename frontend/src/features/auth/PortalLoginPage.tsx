import { useState } from "react";
import { useAuthStore } from "./authStore";

export function PortalLoginPage({ navigate }: { navigate: (path: string, opts?: { replace?: boolean }) => void }) {
  const login = useAuthStore((s) => s.login);
  const loading = useAuthStore((s) => s.loading);
  const authError = useAuthStore((s) => s.authError);
  const clearAuthError = useAuthStore((s) => s.clearAuthError);

  const [email, setEmail] = useState("staff@orderra.test");
  const [password, setPassword] = useState("password");

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
          <h2>Staff and manager access for demo operations</h2>
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
                const result = await login({ email: email.trim(), password, portalType: "portal" });
                navigate(result.redirectTo, { replace: true });
              } catch {
                /* error shown inline */
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
            <div className="inline-notice inline-notice--error field--full">
              <p>{authError}</p>
            </div>
          ) : null}
          <div className="checkout-actions admin-login__actions field--full">
            <button type="submit" className="button button--primary" disabled={loading}>
              {loading ? "Signing in..." : "Sign in"}
            </button>
          </div>
        </form>
      </div>
    </div>
  );
}
