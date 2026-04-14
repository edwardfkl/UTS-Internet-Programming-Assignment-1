import { describe, expect, it } from "vitest";
import { money, parsePrice } from "@/lib/money";

describe("parsePrice", () => {
  it("parses decimal strings from API shape", () => {
    expect(parsePrice({ price: "12.50" })).toBeCloseTo(12.5);
  });
});

describe("money", () => {
  it("formats as HKD", () => {
    expect(money.format(99.9)).toMatch(/99/);
    expect(money.format(99.9)).toMatch(/HK/);
  });
});
