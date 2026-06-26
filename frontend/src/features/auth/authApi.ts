import { apiRequest } from "../../lib/api/client";
import { readAuthToken } from "../../lib/auth/sessionToken";

export type OrderraRole = "customer" | "staff" | "admin" | string;

export interface AuthUser {
  id: number;
  name: string;
  email: string;
  orderra_role: OrderraRole;
}

type AuthPayload = {
  token: string;
  token_type: string;
  redirect_to: string;
  user: AuthUser;
};

export async function loginApi(payload: {
  email: string;
  password: string;
  portal_type?: "customer" | "portal";
}): Promise<AuthPayload> {
  const response = await apiRequest<{ data: AuthPayload }>("/auth/login", {
    method: "POST",
    body: JSON.stringify(payload),
  });

  return response.data;
}

export async function registerCustomerApi(payload: {
  name: string;
  email: string;
  password: string;
}): Promise<AuthPayload> {
  const response = await apiRequest<{ data: AuthPayload }>("/auth/register", {
    method: "POST",
    body: JSON.stringify(payload),
  });

  return response.data;
}

export async function meApi(token = readAuthToken()): Promise<{ user: AuthUser; redirect_to: string }> {
  if (!token) {
    throw new Error("Missing auth token.");
  }

  const response = await apiRequest<{ data: { user: AuthUser; redirect_to: string } }>("/auth/me", {
    method: "GET",
    headers: {
      Authorization: `Bearer ${token}`,
    },
  });

  return response.data;
}

export async function logoutApi(token = readAuthToken()): Promise<void> {
  if (!token) return;

  await apiRequest("/auth/logout", {
    method: "POST",
    headers: {
      Authorization: `Bearer ${token}`,
    },
  });
}
