import { act, renderHook } from "@testing-library/react";
import type { ReactNode } from "react";
import { beforeEach, describe, expect, it } from "vitest";
import {
  CURRENCY_STORAGE_KEY,
  DEFAULT_CURRENCY,
} from "@/lib/currencies";
import { CurrencyProvider, useCurrency } from "@/contexts/currency-context";

function wrapper({ children }: { children: ReactNode }) {
  return <CurrencyProvider>{children}</CurrencyProvider>;
}

describe("CurrencyProvider", () => {
  beforeEach(() => {
    window.localStorage.clear();
  });

  it("defaults to HKD", () => {
    const { result } = renderHook(() => useCurrency(), { wrapper });
    expect(result.current.currency).toBe(DEFAULT_CURRENCY);
  });

  it("reads stored currency on mount", () => {
    window.localStorage.setItem(CURRENCY_STORAGE_KEY, "USD");
    const { result } = renderHook(() => useCurrency(), { wrapper });
    expect(result.current.currency).toBe("USD");
  });

  it("persists currency changes", () => {
    const { result } = renderHook(() => useCurrency(), { wrapper });

    act(() => {
      result.current.setCurrency("JPY");
    });

    expect(result.current.currency).toBe("JPY");
    expect(window.localStorage.getItem(CURRENCY_STORAGE_KEY)).toBe("JPY");
    expect(result.current.formatMoney(100)).toMatch(/1,950|1950/);
  });
});
