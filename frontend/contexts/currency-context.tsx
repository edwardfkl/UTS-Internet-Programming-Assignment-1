"use client";

import {
  createContext,
  useCallback,
  useContext,
  useMemo,
  useState,
} from "react";
import {
  CURRENCY_STORAGE_KEY,
  DEFAULT_CURRENCY,
  type AppCurrency,
} from "@/lib/currencies";
import { formatMoney as formatMoneyLib } from "@/lib/money";

function readStoredCurrency(): AppCurrency {
  if (typeof window === "undefined") return DEFAULT_CURRENCY;
  const raw = window.localStorage.getItem(CURRENCY_STORAGE_KEY);
  if (
    raw === "HKD" ||
    raw === "USD" ||
    raw === "EUR" ||
    raw === "GBP" ||
    raw === "AUD" ||
    raw === "JPY" ||
    raw === "CNY" ||
    raw === "TWD" ||
    raw === "KRW"
  ) {
    return raw;
  }
  return DEFAULT_CURRENCY;
}

type CurrencyContextValue = {
  currency: AppCurrency;
  setCurrency: (next: AppCurrency) => void;
  formatMoney: (amountHkd: number) => string;
};

const CurrencyContext = createContext<CurrencyContextValue | null>(null);

export function CurrencyProvider({ children }: { children: React.ReactNode }) {
  const [currency, setCurrencyState] = useState<AppCurrency>(() =>
    typeof window === "undefined" ? DEFAULT_CURRENCY : readStoredCurrency(),
  );

  const setCurrency = useCallback((next: AppCurrency) => {
    setCurrencyState((prev) => {
      if (prev === next) return prev;
      if (typeof window !== "undefined") {
        window.localStorage.setItem(CURRENCY_STORAGE_KEY, next);
      }
      return next;
    });
  }, []);

  const formatMoney = useCallback(
    (amountHkd: number) => formatMoneyLib(amountHkd, currency),
    [currency],
  );

  const value = useMemo(
    () => ({ currency, setCurrency, formatMoney }),
    [currency, setCurrency, formatMoney],
  );

  return (
    <CurrencyContext.Provider value={value}>{children}</CurrencyContext.Provider>
  );
}

export function useCurrency(): CurrencyContextValue {
  const ctx = useContext(CurrencyContext);
  if (!ctx) {
    throw new Error("useCurrency must be used within CurrencyProvider");
  }
  return ctx;
}
