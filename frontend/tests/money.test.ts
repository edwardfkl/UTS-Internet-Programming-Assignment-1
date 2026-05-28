import { describe, expect, it } from "vitest";
import {
  EXCHANGE_RATES_FROM_AUD,
  SUPPORTED_CURRENCIES,
} from "@/lib/currencies";
import {
  convertFromAud,
  createMoneyFormatter,
  formatMoney,
  money,
  parsePrice,
} from "@/lib/money";

describe("parsePrice", () => {
  it("parses decimal strings from API shape", () => {
    expect(parsePrice({ price: "12.50" })).toBeCloseTo(12.5);
  });

  it("parses integer catalogue prices", () => {
    expect(parsePrice({ price: "899" })).toBe(899);
  });
});

describe("convertFromAud", () => {
  it("returns the same amount for AUD", () => {
    expect(convertFromAud(249.5, "AUD")).toBe(249.5);
  });

  it("converts into HKD for menu display", () => {
    expect(convertFromAud(100, "HKD")).toBeCloseTo(510.2);
  });

  it("converts into USD using the configured rate", () => {
    expect(convertFromAud(100, "USD")).toBeCloseTo(65.3);
  });

  it.each(SUPPORTED_CURRENCIES)("converts zero AUD to zero %s", (code) => {
    expect(convertFromAud(0, code)).toBe(0);
  });

  it.each(SUPPORTED_CURRENCIES)(
    "applies the configured rate for %s",
    (code) => {
      expect(convertFromAud(10, code)).toBeCloseTo(
        10 * EXCHANGE_RATES_FROM_AUD[code],
      );
    },
  );
});

describe("formatMoney", () => {
  it("formats AUD by default", () => {
    expect(formatMoney(99.9)).toMatch(/99/);
    expect(formatMoney(99.9)).toMatch(/\$/);
  });

  it("formats explicit AUD totals", () => {
    expect(formatMoney(899, "AUD")).toMatch(/899/);
  });

  it("formats HKD as a converted display amount", () => {
    const formatted = formatMoney(100, "HKD");
    expect(formatted).toMatch(/510/);
    expect(formatted).toMatch(/HK|HK\$|\$/);
  });

  it("formats USD from an AUD base amount", () => {
    expect(formatMoney(100, "USD")).toMatch(/65\.3/);
  });

  it("formats zero in AUD", () => {
    expect(formatMoney(0, "AUD")).toMatch(/0\.00|0,00/);
  });

  it.each(SUPPORTED_CURRENCIES)("returns a non-empty string for %s", (code) => {
    expect(formatMoney(42, code).length).toBeGreaterThan(0);
  });
});

describe("createMoneyFormatter", () => {
  it("creates locale-aware JPY formatters", () => {
    expect(createMoneyFormatter("JPY").format(9950)).toMatch(/9,950|9950/);
  });

  it("creates an HKD formatter using the zh-HK locale hint", () => {
    const formatted = createMoneyFormatter("HKD").format(510.2);
    expect(formatted).toMatch(/510/);
  });

  it("deprecated money export still targets AUD", () => {
    expect(money.format(10)).toMatch(/10/);
    expect(money.resolvedOptions().currency).toBe("AUD");
  });
});
