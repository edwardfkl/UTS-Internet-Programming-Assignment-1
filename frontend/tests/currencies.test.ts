import { describe, expect, it } from "vitest";
import {
  CURRENCY_INTL_LOCALE,
  CURRENCY_LABELS,
  CURRENCY_STORAGE_KEY,
  DEFAULT_CURRENCY,
  EXCHANGE_RATES_FROM_AUD,
  SUPPORTED_CURRENCIES,
  type AppCurrency,
} from "@/lib/currencies";

describe("currencies config", () => {
  it("defaults to AUD", () => {
    expect(DEFAULT_CURRENCY).toBe("AUD");
  });

  it("lists AUD first and keeps HKD in the menu", () => {
    expect(SUPPORTED_CURRENCIES[0]).toBe("AUD");
    expect(SUPPORTED_CURRENCIES).toContain("HKD");
  });

  it("uses a stable localStorage key", () => {
    expect(CURRENCY_STORAGE_KEY).toBe("edward_store_currency");
  });

  it("defines rates, labels, and locales for every supported currency", () => {
    for (const code of SUPPORTED_CURRENCIES) {
      expect(EXCHANGE_RATES_FROM_AUD[code]).toBeGreaterThan(0);
      expect(CURRENCY_LABELS[code]).toBeTruthy();
      expect(CURRENCY_INTL_LOCALE[code]).toBeTruthy();
    }
  });

  it("AUD rate is identity", () => {
    expect(EXCHANGE_RATES_FROM_AUD.AUD).toBe(1);
  });

  it("HKD is a display conversion from AUD, not the base currency", () => {
    expect(DEFAULT_CURRENCY).not.toBe("HKD");
    expect(EXCHANGE_RATES_FROM_AUD.HKD).toBeGreaterThan(1);
    expect(CURRENCY_LABELS.HKD).toContain("HK$");
    expect(CURRENCY_LABELS.HKD).toContain("HKD");
  });

  it("labels distinguish major dollar currencies", () => {
    expect(CURRENCY_LABELS.AUD).toContain("A$");
    expect(CURRENCY_LABELS.USD).toContain("US$");
    expect(CURRENCY_LABELS.HKD).toContain("HK$");
  });

  it("covers exactly the supported currency codes in rate tables", () => {
    const rateKeys = Object.keys(EXCHANGE_RATES_FROM_AUD).sort();
    expect(rateKeys).toEqual([...SUPPORTED_CURRENCIES].sort());
  });

  it.each(SUPPORTED_CURRENCIES satisfies AppCurrency[])(
    "round-trips zero through %s rate",
    (code) => {
      expect(0 * EXCHANGE_RATES_FROM_AUD[code]).toBe(0);
    },
  );
});
