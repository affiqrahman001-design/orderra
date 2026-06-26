import { create } from "zustand";
import { writeAdminBearer } from "../../lib/auth/adminToken";
import { readAuthToken, writeAuthToken } from "../../lib/auth/sessionToken";
import { loginApi, logoutApi, meApi, registerCustomerApi, type AuthUser } from "./authApi";

type LoginInput = {
  email: string;
  password: string;
  portalType?: "customer" | "portal";
};

type AuthState = {
  token: string | null;
  user: AuthUser | null;
  loading: boolean;
  hasBooted: boolean;
  isBooting: boolean;
  authError: string | null;
  boot: () => Promise<void>;
  login: (input: LoginInput) => Promise<{ redirectTo: string }>;
  registerCustomer: (input: { name: string; email: string; password: string }) => Promise<{ redirectTo: string }>;
  logout: () => Promise<void>;
  refreshSession: () => Promise<void>;
  clearAuthError: () => void;
};

function syncPortalToken(role: string, token: string | null): void {
  if (role === "admin" || role === "staff") {
    writeAdminBearer(token);
    return;
  }

  writeAdminBearer(null);
}

export const useAuthStore = create<AuthState>((set, get) => ({
  token: typeof window !== "undefined" ? readAuthToken() : null,
  user: null,
  loading: true,
  hasBooted: false,
  isBooting: false,
  authError: null,

  clearAuthError() {
    set({ authError: null });
  },

  async boot() {
    const state = get();
    if (state.hasBooted || state.isBooting) {
      return;
    }

    set({ isBooting: true });
    set({ loading: true, authError: null });
    const token = readAuthToken();
    set({ token });

    if (!token) {
      writeAdminBearer(null);
      set({ user: null, loading: false, hasBooted: true, isBooting: false });
      return;
    }

    try {
      const data = await meApi(token);
      syncPortalToken(data.user.orderra_role, token);
      set({ user: data.user, loading: false, hasBooted: true, isBooting: false });
    } catch {
      writeAuthToken(null);
      writeAdminBearer(null);
      set({ token: null, user: null, loading: false, hasBooted: true, isBooting: false });
    }
  },

  async refreshSession() {
    const token = get().token ?? readAuthToken();
    if (!token) {
      set({ user: null });
      return;
    }

    try {
      const data = await meApi(token);
      syncPortalToken(data.user.orderra_role, token);
      set({ user: data.user, authError: null });
    } catch {
      writeAuthToken(null);
      writeAdminBearer(null);
      set({ token: null, user: null });
    }
  },

  async login({ email, password, portalType }) {
    set({ loading: true, authError: null });
    try {
      const data = await loginApi({
        email,
        password,
        portal_type: portalType,
      });

      writeAuthToken(data.token);
      syncPortalToken(data.user.orderra_role, data.token);
      set({ token: data.token, user: data.user, loading: false });
      return { redirectTo: data.redirect_to };
    } catch (error) {
      set({
        loading: false,
        authError: error instanceof Error ? error.message : "Sign-in could not complete.",
      });
      throw error;
    }
  },

  async registerCustomer({ name, email, password }) {
    set({ loading: true, authError: null });
    try {
      const data = await registerCustomerApi({ name, email, password });
      writeAuthToken(data.token);
      writeAdminBearer(null);
      set({ token: data.token, user: data.user, loading: false });
      return { redirectTo: data.redirect_to };
    } catch (error) {
      set({
        loading: false,
        authError: error instanceof Error ? error.message : "Registration could not complete.",
      });
      throw error;
    }
  },

  async logout() {
    const token = get().token ?? readAuthToken();
    try {
      await logoutApi(token);
    } catch {
      /* session may already be invalidated */
    }

    writeAuthToken(null);
    writeAdminBearer(null);
    set({ token: null, user: null, authError: null, loading: false });
  },
}));

export function isCustomerRole(role: string | null | undefined): boolean {
  return role === "customer";
}

export function isStaffRole(role: string | null | undefined): boolean {
  return role === "staff";
}

export function isAdminRole(role: string | null | undefined): boolean {
  return role === "admin";
}

export function canAccessPortal(role: string | null | undefined): boolean {
  return isAdminRole(role) || isStaffRole(role);
}
