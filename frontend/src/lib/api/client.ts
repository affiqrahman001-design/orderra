import { ADMIN_BEARER_STORAGE_KEY } from "../auth/adminToken";
import { appConfig } from "../config/env";

export class ApiError extends Error {
  status: number;
  payload: unknown;

  constructor(message: string, status: number, payload: unknown) {
    super(message);
    this.name = "ApiError";
    this.status = status;
    this.payload = payload;
  }
}

export interface ApiRequestOptions extends RequestInit {
  admin?: boolean;
  cartToken?: string | null;
}

function buildUrl(path: string): string {
  if (/^https?:\/\//i.test(path)) return path;
  return `${appConfig.apiBaseUrl}${path.startsWith("/") ? path : `/${path}`}`;
}

function getPayloadMessage(payload: unknown, fallback: string): string {
  if (payload && typeof payload === "object") {
    const record = payload as Record<string, unknown>;

    if (typeof record.message === "string" && record.message.trim()) {
      return record.message;
    }

    if (record.errors && typeof record.errors === "object") {
      const firstError = Object.values(record.errors as Record<string, unknown>)[0];

      if (Array.isArray(firstError) && typeof firstError[0] === "string") {
        return firstError[0];
      }
    }
  }

  return fallback;
}

export async function apiRequest<T>(
  path: string,
  options: ApiRequestOptions = {},
): Promise<T> {
  const { admin = false, cartToken, headers: initHeaders, body, ...init } = options;
  const headers = new Headers(initHeaders);

  if (!headers.has("Accept")) {
    headers.set("Accept", "application/json");
  }

  if (body !== undefined && !(body instanceof FormData) && !headers.has("Content-Type")) {
    headers.set("Content-Type", "application/json");
  }

  if (cartToken) {
    headers.set("X-Cart-Token", cartToken);
  }

  if (admin) {
    const storedBearer =
      typeof window !== "undefined" ? window.localStorage.getItem(ADMIN_BEARER_STORAGE_KEY) : null;
    if (!headers.has("Authorization") && storedBearer) {
      headers.set("Authorization", `Bearer ${storedBearer}`);
    }

    if (!headers.has("Authorization") && appConfig.adminReferenceKey) {
      headers.set(appConfig.adminReferenceHeader, appConfig.adminReferenceKey);
    }
  }

  const response = await fetch(buildUrl(path), {
    ...init,
    body,
    headers,
  });

  const contentType = response.headers.get("content-type") ?? "";
  const payload = contentType.includes("application/json")
    ? ((await response.json()) as unknown)
    : await response.text();

  if (!response.ok) {
    throw new ApiError(
      getPayloadMessage(payload, `Request failed with status ${response.status}`),
      response.status,
      payload,
    );
  }

  return payload as T;
}

export function buildQuery(
  params?: Record<string, string | number | boolean | null | undefined>,
): string {
  if (!params) return "";

  const query = new URLSearchParams();

  Object.entries(params).forEach(([key, value]) => {
    if (value !== undefined && value !== null && value !== "") {
      query.set(key, String(value));
    }
  });

  const serialized = query.toString();

  return serialized ? `?${serialized}` : "";
}
