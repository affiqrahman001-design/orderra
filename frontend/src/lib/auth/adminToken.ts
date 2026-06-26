export const ADMIN_BEARER_STORAGE_KEY = "orderra_admin_token";

export function readAdminBearer(): string | null {
  if (typeof window === "undefined") return null;
  return window.localStorage.getItem(ADMIN_BEARER_STORAGE_KEY);
}

export function writeAdminBearer(token: string | null): void {
  if (typeof window === "undefined") return;
  if (token) window.localStorage.setItem(ADMIN_BEARER_STORAGE_KEY, token);
  else window.localStorage.removeItem(ADMIN_BEARER_STORAGE_KEY);
}
