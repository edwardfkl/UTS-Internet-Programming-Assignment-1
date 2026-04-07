"use client";

import {
  createContext,
  useCallback,
  useContext,
  useEffect,
  useMemo,
  useState,
} from "react";
import { getStoredCartToken } from "@/lib/api";
import { apiFetchUser, apiLogin, apiLogout, apiRegister, attachCartToUser } from "@/lib/authApi";
import { clearAuthToken, getAuthToken, setAuthToken } from "@/lib/authToken";
import type { AuthUser } from "@/lib/types";

type AuthContextValue = {
  user: AuthUser | null;
  ready: boolean;
  login: (email: string, password: string) => Promise<void>;
  register: (
    name: string,
    email: string,
    password: string,
    passwordConfirmation: string,
  ) => Promise<void>;
  logout: () => Promise<void>;
};

const AuthContext = createContext<AuthContextValue | null>(null);

async function linkGuestCartIfAny(): Promise<void> {
  const cart = getStoredCartToken();
  if (!cart) return;
  try {
    await attachCartToUser(cart);
  } catch {
    /* guest cart may already belong elsewhere */
  }
}

export function AuthProvider({ children }: { children: React.ReactNode }) {
  const [user, setUser] = useState<AuthUser | null>(null);
  const [ready, setReady] = useState(false);

  useEffect(() => {
    let cancelled = false;
    const token = getAuthToken();
    if (!token) {
      setReady(true);
      return () => {
        cancelled = true;
      };
    }
    void (async () => {
      try {
        const u = await apiFetchUser(token);
        if (!cancelled) setUser(u);
      } catch {
        clearAuthToken();
        if (!cancelled) setUser(null);
      } finally {
        if (!cancelled) setReady(true);
      }
    })();
    return () => {
      cancelled = true;
    };
  }, []);

  const login = useCallback(async (email: string, password: string) => {
    const { user: u, token } = await apiLogin(email, password);
    setAuthToken(token);
    setUser(u);
    await linkGuestCartIfAny();
  }, []);

  const register = useCallback(
    async (
      name: string,
      email: string,
      password: string,
      passwordConfirmation: string,
    ) => {
      const { user: u, token } = await apiRegister(
        name,
        email,
        password,
        passwordConfirmation,
      );
      setAuthToken(token);
      setUser(u);
      await linkGuestCartIfAny();
    },
    [],
  );

  const logout = useCallback(async () => {
    try {
      await apiLogout();
    } finally {
      clearAuthToken();
      setUser(null);
    }
  }, []);

  const value = useMemo(
    () => ({
      user,
      ready,
      login,
      register,
      logout,
    }),
    [user, ready, login, register, logout],
  );

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>;
}

export function useAuth(): AuthContextValue {
  const ctx = useContext(AuthContext);
  if (!ctx) {
    throw new Error("useAuth must be used within AuthProvider");
  }
  return ctx;
}
