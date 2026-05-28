import { act, renderHook } from "@testing-library/react";
import type { ReactNode } from "react";
import { beforeEach, describe, expect, it, vi } from "vitest";
import { LocaleProvider, useLocale } from "@/contexts/locale-context";
import { LOCALE_STORAGE_KEY, SUPPORTED_LOCALES } from "@/lib/locales";

vi.mock("@/lib/localeSync", async () => {
  const actual = await vi.importActual<typeof import("@/lib/localeSync")>(
    "@/lib/localeSync",
  );
  return {
    ...actual,
    fetchServerLocale: vi.fn(() => Promise.resolve(null)),
    pushServerLocale: vi.fn(() => Promise.resolve()),
  };
});

function wrapper({ children }: { children: ReactNode }) {
  return <LocaleProvider>{children}</LocaleProvider>;
}

describe("LocaleProvider", () => {
  beforeEach(() => {
    window.localStorage.clear();
  });

  it("translates known keys in English", () => {
    const { result } = renderHook(() => useLocale(), { wrapper });
    expect(result.current.t("cart.title")).toBe("Cart");
  });

  it("returns the key when a translation is missing", () => {
    const { result } = renderHook(() => useLocale(), { wrapper });
    expect(result.current.t("does.not.exist")).toBe("does.not.exist");
  });

  it("interpolates variables with tf", () => {
    const { result } = renderHook(() => useLocale(), { wrapper });
    const text = result.current.tf("payDetail.quoteRef", {
      reference: "SSP-001",
    });
    expect(text).toContain("SSP-001");
  });

  it.each(SUPPORTED_LOCALES.filter((locale) => locale !== "en"))(
    "persists %s locale changes",
    (locale) => {
      const { result } = renderHook(() => useLocale(), { wrapper });

      act(() => {
        result.current.setLocale(locale);
      });

      expect(result.current.locale).toBe(locale);
      expect(window.localStorage.getItem(LOCALE_STORAGE_KEY)).toBe(locale);
      expect(result.current.t("cart.title")).toBeTruthy();
    },
  );

  it("keeps English as the default without requiring storage", () => {
    const { result } = renderHook(() => useLocale(), { wrapper });
    expect(result.current.locale).toBe("en");
  });

  it("falls back to English for invalid stored locale", () => {
    window.localStorage.setItem(LOCALE_STORAGE_KEY, "fr");
    const { result } = renderHook(() => useLocale(), { wrapper });
    expect(result.current.locale).toBe("en");
  });

  it("does not rewrite localStorage when selecting the current locale", () => {
    window.localStorage.setItem(LOCALE_STORAGE_KEY, "ja");
    const { result } = renderHook(() => useLocale(), { wrapper });

    act(() => {
      result.current.setLocale("ja");
    });

    expect(result.current.locale).toBe("ja");
    expect(window.localStorage.getItem(LOCALE_STORAGE_KEY)).toBe("ja");
  });
});

describe("useLocale", () => {
  it("throws outside LocaleProvider", () => {
    expect(() => renderHook(() => useLocale())).toThrow(
      "useLocale must be used within LocaleProvider",
    );
  });
});
