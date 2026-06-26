export const AUTH_SESSION_TOKEN_STORAGE_KEY = "orderra_auth_token";

export function readAuthToken(): string | null {
  if (typeof window === "undefined") return null;
  return window.localStorage.getItem(AUTH_SESSION_TOKEN_STORAGE_KEY);
}

export function writeAuthToken(token: string | null): void {
  if (typeof window === "undefined") return;
  if (token) window.localStorage.setItem(AUTH_SESSION_TOKEN_STORAGE_KEY, token);
  else window.localStorage.removeItem(AUTH_SESSION_TOKEN_STORAGE_KEY);
}
