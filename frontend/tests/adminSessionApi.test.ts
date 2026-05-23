import { afterEach, beforeEach, describe, expect, it, vi } from "vitest";
import {
  clearAdminWebSession,
  syncAdminWebSession,
} from "@/lib/adminSessionApi";
import { setAuthToken, clearAuthToken } from "@/lib/authToken";
import {
  headerValue,
  installFetchMock,
  jsonResponse,
  TEST_API_BASE,
} from "./helpers/fetchMock";

describe("adminSessionApi", () => {
  beforeEach(() => {
    window.localStorage.clear();
    vi.stubEnv("NEXT_PUBLIC_API_URL", TEST_API_BASE);
  });

  afterEach(() => {
    vi.unstubAllGlobals();
  });

  it("syncAdminWebSession no-ops without auth token", async () => {
    const fetchMock = installFetchMock(() => jsonResponse({}));
    await syncAdminWebSession();
    expect(fetchMock).not.toHaveBeenCalled();
  });

  it("syncAdminWebSession POSTs with credentials", async () => {
    setAuthToken("admin-jwt");
    const fetchMock = installFetchMock((url, init) => {
      if (url.endsWith("/api/admin/web-session") && init?.method === "POST") {
        expect(headerValue(init, "Authorization")).toBe("Bearer admin-jwt");
        expect(init?.credentials).toBe("include");
        return jsonResponse({ ok: true });
      }
      return jsonResponse({}, 404);
    });
    await syncAdminWebSession();
    expect(fetchMock).toHaveBeenCalledOnce();
    clearAuthToken();
  });

  it("clearAdminWebSession swallows network errors", async () => {
    setAuthToken("admin-jwt");
    installFetchMock(() => {
      throw new Error("offline");
    });
    await expect(clearAdminWebSession()).resolves.toBeUndefined();
    clearAuthToken();
  });
});
