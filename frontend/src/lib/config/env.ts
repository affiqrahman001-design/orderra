export type DataMode = "api" | "local";

const DEFAULT_API_BASE_URL = "http://127.0.0.1:8000/api/v1";

function trimTrailingSlash(value: string): string {
  return value.replace(/\/+$/, "");
}

function normalizeDataMode(value: unknown): DataMode {
  return value === "api" || value === "http" ? "api" : "local";
}

export const appConfig = {
  dataMode: normalizeDataMode(
    import.meta.env.VITE_DATA_MODE ?? import.meta.env.VITE_DATA_SOURCE ?? "local",
  ),
  apiBaseUrl: trimTrailingSlash(import.meta.env.VITE_API_BASE_URL || DEFAULT_API_BASE_URL),
  adminReferenceKey: import.meta.env.VITE_ADMIN_REFERENCE_KEY || "",
  adminReferenceHeader: import.meta.env.VITE_ADMIN_REFERENCE_HEADER || "X-ORDERra-Admin-Key",
  paymentSimulationOutcome: import.meta.env.VITE_PAYMENT_SIMULATION_OUTCOME || "success",
} as const;

export function isApiMode(): boolean {
  return appConfig.dataMode === "api";
}
