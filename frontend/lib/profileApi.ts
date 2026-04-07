import { apiBase } from "@/lib/api";
import { getAuthToken } from "@/lib/authToken";
import type { UserProfile } from "@/lib/types";

function authHeaders(): HeadersInit {
  const t = getAuthToken();
  if (!t) throw new Error("Not signed in");
  return {
    Accept: "application/json",
    Authorization: `Bearer ${t}`,
  };
}

export async function fetchProfile(): Promise<UserProfile> {
  const res = await fetch(`${apiBase()}/api/profile`, {
    headers: authHeaders(),
    cache: "no-store",
  });
  if (!res.ok) {
    throw new Error(`Could not load profile (HTTP ${res.status})`);
  }
  return res.json() as Promise<UserProfile>;
}

export async function updateProfile(
  patch: Partial<Omit<UserProfile, "id" | "email">>,
): Promise<UserProfile> {
  const res = await fetch(`${apiBase()}/api/profile`, {
    method: "PATCH",
    headers: {
      ...authHeaders(),
      "Content-Type": "application/json",
    },
    body: JSON.stringify(patch),
  });
  if (!res.ok) {
    let msg = `Could not update profile (HTTP ${res.status})`;
    try {
      const body = (await res.json()) as { message?: string; errors?: Record<string, string[]> };
      const first = body.errors && Object.values(body.errors)[0]?.[0];
      if (first) msg = first;
      else if (body.message) msg = body.message;
    } catch {
      /* ignore */
    }
    throw new Error(msg);
  }
  return res.json() as Promise<UserProfile>;
}
