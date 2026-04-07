import { apiBase } from "@/lib/api";
import { getAuthToken } from "@/lib/authToken";
import type { AuthUser } from "@/lib/types";

export async function apiRegister(
  name: string,
  email: string,
  password: string,
  passwordConfirmation: string,
): Promise<{ user: AuthUser; token: string }> {
  const res = await fetch(`${apiBase()}/api/register`, {
    method: "POST",
    headers: {
      Accept: "application/json",
      "Content-Type": "application/json",
    },
    body: JSON.stringify({
      name,
      email,
      password,
      password_confirmation: passwordConfirmation,
    }),
  });
  if (!res.ok) {
    let msg = `Registration failed (HTTP ${res.status})`;
    try {
      const body = (await res.json()) as {
        message?: string;
        errors?: Record<string, string[]>;
      };
      const first = body.errors && Object.values(body.errors)[0]?.[0];
      if (first) msg = first;
      else if (body.message) msg = body.message;
    } catch {
      /* ignore */
    }
    throw new Error(msg);
  }
  return res.json() as Promise<{ user: AuthUser; token: string }>;
}

export async function apiLogin(
  email: string,
  password: string,
): Promise<{ user: AuthUser; token: string }> {
  const res = await fetch(`${apiBase()}/api/login`, {
    method: "POST",
    headers: {
      Accept: "application/json",
      "Content-Type": "application/json",
    },
    body: JSON.stringify({ email, password }),
  });
  if (!res.ok) {
    let msg = "Invalid email or password.";
    try {
      const body = (await res.json()) as { message?: string };
      if (body.message) msg = body.message;
    } catch {
      /* ignore */
    }
    throw new Error(msg);
  }
  return res.json() as Promise<{ user: AuthUser; token: string }>;
}

export async function apiLogout(): Promise<void> {
  const token = getAuthToken();
  if (!token) return;
  await fetch(`${apiBase()}/api/logout`, {
    method: "POST",
    headers: {
      Accept: "application/json",
      Authorization: `Bearer ${token}`,
    },
  });
}

export async function apiFetchUser(token: string): Promise<AuthUser> {
  const res = await fetch(`${apiBase()}/api/user`, {
    headers: {
      Accept: "application/json",
      Authorization: `Bearer ${token}`,
    },
    cache: "no-store",
  });
  if (!res.ok) {
    throw new Error("Session expired");
  }
  return res.json() as Promise<AuthUser>;
}

export async function attachCartToUser(cartToken: string): Promise<void> {
  const auth = getAuthToken();
  if (!auth) return;
  const res = await fetch(`${apiBase()}/api/cart/attach`, {
    method: "POST",
    headers: {
      Accept: "application/json",
      Authorization: `Bearer ${auth}`,
      "X-Cart-Token": cartToken,
    },
  });
  if (!res.ok) {
    throw new Error(`Could not link cart to account (HTTP ${res.status})`);
  }
}
