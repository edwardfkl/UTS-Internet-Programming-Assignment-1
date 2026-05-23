import { act, renderHook, waitFor } from "@testing-library/react";
import type { ReactNode } from "react";
import { afterEach, beforeEach, describe, expect, it, vi } from "vitest";
import { AuthProvider, useAuth } from "@/contexts/auth-context";
import { setAuthToken } from "@/lib/authToken";

const authUser = {
  id: 1,
  name: "Ada",
  email: "ada@example.com",
  avatar_url: null,
  is_admin: false,
};

const apiMocks = vi.hoisted(() => ({
  apiLogin: vi.fn(),
  apiRegister: vi.fn(),
  apiLogout: vi.fn(),
  apiFetchUser: vi.fn(),
  attachCartToUser: vi.fn(),
  syncAdminWebSession: vi.fn(),
  clearAdminWebSession: vi.fn(),
  resetCartSession: vi.fn(),
  getStoredCartToken: vi.fn(),
}));

vi.mock("@/lib/authApi", () => ({
  apiLogin: apiMocks.apiLogin,
  apiRegister: apiMocks.apiRegister,
  apiLogout: apiMocks.apiLogout,
  apiFetchUser: apiMocks.apiFetchUser,
  attachCartToUser: apiMocks.attachCartToUser,
}));

vi.mock("@/lib/adminSessionApi", () => ({
  syncAdminWebSession: apiMocks.syncAdminWebSession,
  clearAdminWebSession: apiMocks.clearAdminWebSession,
}));

vi.mock("@/lib/api", () => ({
  getStoredCartToken: apiMocks.getStoredCartToken,
  resetCartSession: apiMocks.resetCartSession,
}));

function wrapper({ children }: { children: ReactNode }) {
  return <AuthProvider>{children}</AuthProvider>;
}

describe("AuthProvider", () => {
  beforeEach(() => {
    window.localStorage.clear();
    vi.clearAllMocks();
    apiMocks.apiFetchUser.mockResolvedValue(authUser);
    apiMocks.apiLogin.mockResolvedValue({ user: authUser, token: "jwt-new" });
    apiMocks.apiRegister.mockResolvedValue({ user: authUser, token: "jwt-reg" });
    apiMocks.apiLogout.mockResolvedValue(undefined);
    apiMocks.attachCartToUser.mockResolvedValue(undefined);
    apiMocks.syncAdminWebSession.mockResolvedValue(undefined);
    apiMocks.clearAdminWebSession.mockResolvedValue(undefined);
    apiMocks.getStoredCartToken.mockReturnValue(null);
    apiMocks.resetCartSession.mockResolvedValue("new-cart");
  });

  afterEach(() => {
    vi.unstubAllGlobals();
  });

  it("becomes ready with no user when there is no token", async () => {
    const { result } = renderHook(() => useAuth(), { wrapper });

    await waitFor(() => {
      expect(result.current.ready).toBe(true);
    });
    expect(result.current.user).toBeNull();
    expect(apiMocks.apiFetchUser).not.toHaveBeenCalled();
  });

  it("restores session from stored token on mount", async () => {
    setAuthToken("jwt-stored");
    const { result } = renderHook(() => useAuth(), { wrapper });

    await waitFor(() => {
      expect(result.current.ready).toBe(true);
    });
    expect(apiMocks.apiFetchUser).toHaveBeenCalledWith("jwt-stored");
    expect(result.current.user?.email).toBe("ada@example.com");
  });

  it("login stores token, sets user, and links guest cart", async () => {
    apiMocks.getStoredCartToken.mockReturnValue("guest-cart");
    const { result } = renderHook(() => useAuth(), { wrapper });

    await waitFor(() => expect(result.current.ready).toBe(true));

    await act(async () => {
      await result.current.login("ada@example.com", "secret");
    });

    expect(apiMocks.apiLogin).toHaveBeenCalled();
    expect(apiMocks.attachCartToUser).toHaveBeenCalledWith("guest-cart");
    expect(result.current.user).toEqual(authUser);
  });

  it("logout clears user and admin session", async () => {
    setAuthToken("jwt-stored");
    const { result } = renderHook(() => useAuth(), { wrapper });

    await waitFor(() => expect(result.current.user).not.toBeNull());

    await act(async () => {
      await result.current.logout();
    });

    expect(apiMocks.clearAdminWebSession).toHaveBeenCalled();
    expect(apiMocks.apiLogout).toHaveBeenCalled();
    expect(result.current.user).toBeNull();
  });
});
