import { describe, expect, it } from "vitest";
import {
  convertFromHkd,
  createMoneyFormatter,
  formatMoney,
  parsePrice,
} from "@/lib/money";

describe("parsePrice", () => {
  it("parses decimal strings from API shape", () => {
    expect(parsePrice({ price: "12.50" })).toBeCloseTo(12.5);
  });
});

describe("formatMoney", () => {
  it("formats HKD by default", () => {
    expect(formatMoney(99.9)).toMatch(/99/);
    expect(formatMoney(99.9)).toMatch(/HK/);
  });

  it("converts from HKD base for other currencies", () => {
    expect(convertFromHkd(100, "USD")).toBeCloseTo(12.8);
    expect(formatMoney(100, "USD")).toMatch(/12\.8/);
  });

  it("creates locale-aware formatters", () => {
    expect(createMoneyFormatter("JPY").format(1950)).toMatch(/1,950|1950/);
  });
});
