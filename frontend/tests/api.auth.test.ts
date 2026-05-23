import { afterEach, beforeEach, describe, expect, it, vi } from "vitest";
import {
  apiFetchUser,
  apiLogin,
  apiLogout,
  apiRegister,
  attachCartToUser,
} from "@/lib/authApi";
import { setAuthToken, clearAuthToken } from "@/lib/authToken";
import {
  headerValue,
  installFetchMock,
  jsonResponse,
  TEST_API_BASE,
} from "./helpers/fetchMock";

const user = {
  id: 1,
  name: "Ada",
  email: "ada@example.com",
  avatar_url: null,
  is_admin: false,
};

describe("authApi", () => {
  beforeEach(() => {
    window.localStorage.clear();
    vi.stubEnv("NEXT_PUBLIC_API_URL", TEST_API_BASE);
  });

  afterEach(() => {
    vi.unstubAllGlobals();
  });

  it("apiLogin returns user and token", async () => {
    installFetchMock((url, init) => {
      if (url.endsWith("/api/login") && init?.method === "POST") {
        return jsonResponse({ user, token: "jwt-1" });
      }
      return jsonResponse({}, 404);
    });

    const result = await apiLogin("ada@example.com", "secret");
    expect(result.user.email).toBe("ada@example.com");
    expect(result.token).toBe("jwt-1");
  });

  it("apiLogin surfaces server messages", async () => {
    installFetchMock(() =>
      jsonResponse({ message: "Account suspended." }, 403),
    );
    await expect(apiLogin("a@b.c", "x")).rejects.toThrow("Account suspended.");
  });

  it("apiRegister surfaces validation errors", async () => {
    installFetchMock(() =>
      jsonResponse(
        { errors: { email: ["The email has already been taken."] } },
        422,
      ),
    );
    await expect(
      apiRegister("Ada", "taken@example.com", "pass", "pass"),
    ).rejects.toThrow("The email has already been taken.");
  });

  it("apiFetchUser sends Authorization header", async () => {
    const fetchMock = installFetchMock((url, init) => {
      if (url.endsWith("/api/user")) {
        expect(headerValue(init, "Authorization")).toBe("Bearer jwt-2");
        return jsonResponse(user);
      }
      return jsonResponse({}, 404);
    });

    await expect(apiFetchUser("jwt-2")).resolves.toEqual(user);
    expect(fetchMock).toHaveBeenCalledOnce();
  });

  it("apiFetchUser throws when session expired", async () => {
    installFetchMock(() => jsonResponse({}, 401));
    await expect(apiFetchUser("bad")).rejects.toThrow("Session expired");
  });

  it("apiLogout no-ops without stored token", async () => {
    const fetchMock = installFetchMock(() => jsonResponse({}));
    await apiLogout();
    expect(fetchMock).not.toHaveBeenCalled();
  });

  it("apiLogout POSTs when token exists", async () => {
    setAuthToken("jwt-3");
    const fetchMock = installFetchMock((url, init) => {
      if (url.endsWith("/api/logout") && init?.method === "POST") {
        return jsonResponse({ ok: true });
      }
      return jsonResponse({}, 404);
    });
    await apiLogout();
    expect(fetchMock).toHaveBeenCalledOnce();
    clearAuthToken();
  });

  it("attachCartToUser no-ops when not signed in", async () => {
    const fetchMock = installFetchMock(() => jsonResponse({}));
    await attachCartToUser("cart-token");
    expect(fetchMock).not.toHaveBeenCalled();
  });

  it("attachCartToUser links cart with both tokens", async () => {
    setAuthToken("jwt-4");
    const fetchMock = installFetchMock((url, init) => {
      if (url.endsWith("/api/cart/attach") && init?.method === "POST") {
        expect(headerValue(init, "X-Cart-Token")).toBe("cart-xyz");
        expect(headerValue(init, "Authorization")).toBe("Bearer jwt-4");
        return jsonResponse({ ok: true });
      }
      return jsonResponse({}, 404);
    });
    await attachCartToUser("cart-xyz");
    expect(fetchMock).toHaveBeenCalledOnce();
  });
});
