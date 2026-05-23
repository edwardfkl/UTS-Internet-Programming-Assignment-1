import { describe, expect, it } from "vitest";
import {
  CURRENCY_LABELS,
  DEFAULT_CURRENCY,
  EXCHANGE_RATES_FROM_HKD,
  SUPPORTED_CURRENCIES,
} from "@/lib/currencies";

describe("currencies config", () => {
  it("defaults to HKD", () => {
    expect(DEFAULT_CURRENCY).toBe("HKD");
  });

  it("defines rates and labels for every supported currency", () => {
    for (const code of SUPPORTED_CURRENCIES) {
      expect(EXCHANGE_RATES_FROM_HKD[code]).toBeGreaterThan(0);
      expect(CURRENCY_LABELS[code]).toBeTruthy();
    }
  });

  it("HKD rate is identity", () => {
    expect(EXCHANGE_RATES_FROM_HKD.HKD).toBe(1);
  });
});
