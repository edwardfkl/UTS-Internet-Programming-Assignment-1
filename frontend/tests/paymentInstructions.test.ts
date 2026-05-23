import { describe, expect, it } from "vitest";
import { PAYMENT_OPTIONS, paymentDetailBlocks } from "@/lib/paymentInstructions";

describe("paymentInstructions", () => {
  it("lists all supported payment methods", () => {
    expect(PAYMENT_OPTIONS.map((o) => o.id)).toEqual([
      "atm_transfer",
      "pay_id",
      "bpay",
    ]);
  });

  it("includes order reference in every method block", () => {
    for (const { id } of PAYMENT_OPTIONS) {
      const blocks = paymentDetailBlocks(id, "SSP-000042", "$99.00");
      const text = JSON.stringify(blocks);
      expect(text).toContain("SSP-000042");
      expect(text).toContain("$99.00");
    }
  });

  it("uses translation keys when t is provided", () => {
    const t = (key: string) => `T:${key}`;
    const blocks = paymentDetailBlocks("pay_id", "REF", "10", t);
    expect(blocks[0]?.title).toContain("T:payDetail");
  });
});
