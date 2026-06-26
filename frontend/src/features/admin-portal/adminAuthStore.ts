import { create } from "zustand";
import { canAccessPortal, useAuthStore } from "../auth/authStore";

export type StaffUser = {
  name: string;
  email: string;
  role: string;
};

type AdminAuthState = {
  token: string | null;
  user: StaffUser | null;
  loading: boolean;
  authError: string | null;
  boot: () => Promise<void>;
  login: (email: string, password: string) => Promise<void>;
  logout: () => Promise<void>;
  clearAuthError: () => void;
};

export const useAdminAuthStore = create<AdminAuthState>((set) => ({
  token: null,
  user: null,
  loading: true,
  authError: null,

  clearAuthError() {
    set({ authError: null });
  },

  async boot() {
    set({ loading: true, authError: null });
    const next = useAuthStore.getState();
    const role = next.user?.orderra_role;

    if (!next.token || !next.user || !canAccessPortal(role)) {
      set({ token: null, user: null, loading: false });
      return;
    }

    set({
      token: next.token,
      user: { name: next.user.name, email: next.user.email, role: next.user.orderra_role },
      loading: false,
    });
  },

  async login(email, password) {
    set({ loading: true, authError: null });
    try {
      await useAuthStore.getState().login({ email, password, portalType: "portal" });
      const next = useAuthStore.getState();
      if (!next.token || !next.user) {
        throw new Error("Sign-in could not complete.");
      }
      set({
        token: next.token,
        user: { name: next.user.name, email: next.user.email, role: next.user.orderra_role },
        loading: false,
      });
    } catch (error) {
      set({
        loading: false,
        authError: error instanceof Error ? error.message : "Sign-in could not complete.",
      });
      throw error;
    }
  },

  async logout() {
    await useAuthStore.getState().logout();
    set({ token: null, user: null, loading: false });
  },
}));
