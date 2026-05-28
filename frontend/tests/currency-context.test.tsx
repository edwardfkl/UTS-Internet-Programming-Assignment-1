import { act, renderHook } from "@testing-library/react";
import type { ReactNode } from "react";
import { beforeEach, describe, expect, it } from "vitest";
import {
  CURRENCY_STORAGE_KEY,
  DEFAULT_CURRENCY,
  SUPPORTED_CURRENCIES,
} from "@/lib/currencies";
import { convertFromAud } from "@/lib/money";
import { CurrencyProvider, useCurrency } from "@/contexts/currency-context";

function wrapper({ children }: { children: ReactNode }) {
  return <CurrencyProvider>{children}</CurrencyProvider>;
}

describe("CurrencyProvider", () => {
  beforeEach(() => {
    window.localStorage.clear();
  });

  it("defaults to AUD", () => {
    const { result } = renderHook(() => useCurrency(), { wrapper });
    expect(result.current.currency).toBe(DEFAULT_CURRENCY);
  });

  it.each(SUPPORTED_CURRENCIES)("reads stored %s on mount", (code) => {
    window.localStorage.setItem(CURRENCY_STORAGE_KEY, code);
    const { result } = renderHook(() => useCurrency(), { wrapper });
    expect(result.current.currency).toBe(code);
  });

  it("falls back to AUD for invalid stored values", () => {
    window.localStorage.setItem(CURRENCY_STORAGE_KEY, "GBP?");
    const { result } = renderHook(() => useCurrency(), { wrapper });
    expect(result.current.currency).toBe("AUD");
  });

  it("falls back to AUD when storage is empty", () => {
    window.localStorage.removeItem(CURRENCY_STORAGE_KEY);
    const { result } = renderHook(() => useCurrency(), { wrapper });
    expect(result.current.currency).toBe("AUD");
  });

  it("persists currency changes", () => {
    const { result } = renderHook(() => useCurrency(), { wrapper });

    act(() => {
      result.current.setCurrency("JPY");
    });

    expect(result.current.currency).toBe("JPY");
    expect(window.localStorage.getItem(CURRENCY_STORAGE_KEY)).toBe("JPY");
    expect(result.current.formatMoney(100)).toMatch(/9,950|9950/);
  });

  it("formats HKD through the provider after switching currency", () => {
    const { result } = renderHook(() => useCurrency(), { wrapper });

    act(() => {
      result.current.setCurrency("HKD");
    });

    expect(result.current.currency).toBe("HKD");
    expect(window.localStorage.getItem(CURRENCY_STORAGE_KEY)).toBe("HKD");
    expect(result.current.formatMoney(100)).toMatch(/510/);
    expect(convertFromAud(100, "HKD")).toBeCloseTo(510.2);
  });

  it("does not rewrite localStorage when selecting the current currency", () => {
    window.localStorage.setItem(CURRENCY_STORAGE_KEY, "USD");
    const { result } = renderHook(() => useCurrency(), { wrapper });

    act(() => {
      result.current.setCurrency("USD");
    });

    expect(result.current.currency).toBe("USD");
    expect(window.localStorage.getItem(CURRENCY_STORAGE_KEY)).toBe("USD");
  });

  it("updates formatMoney when currency changes", () => {
    const { result } = renderHook(() => useCurrency(), { wrapper });

    const audFormatted = result.current.formatMoney(100);
    expect(audFormatted).toMatch(/100/);

    act(() => {
      result.current.setCurrency("USD");
    });

    expect(result.current.formatMoney(100)).toMatch(/65\.3/);
    expect(result.current.formatMoney(100)).not.toBe(audFormatted);
  });
});

describe("useCurrency", () => {
  it("throws outside CurrencyProvider", () => {
    expect(() => renderHook(() => useCurrency())).toThrow(
      "useCurrency must be used within CurrencyProvider",
    );
  });
});
