import { act, renderHook } from "@testing-library/react";
import type { ReactNode } from "react";
import { beforeEach, describe, expect, it, vi } from "vitest";
import { LocaleProvider, useLocale } from "@/contexts/locale-context";
import { LOCALE_STORAGE_KEY } from "@/lib/locales";

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

  it("interpolates variables with tf", () => {
    const { result } = renderHook(() => useLocale(), { wrapper });
    const text = result.current.tf("payDetail.quoteRef", {
      reference: "SSP-001",
    });
    expect(text).toContain("SSP-001");
  });

  it("persists locale changes", () => {
    const { result } = renderHook(() => useLocale(), { wrapper });

    act(() => {
      result.current.setLocale("ja");
    });

    expect(result.current.locale).toBe("ja");
    expect(window.localStorage.getItem(LOCALE_STORAGE_KEY)).toBe("ja");
    expect(result.current.t("cart.title")).toBeTruthy();
  });
});
