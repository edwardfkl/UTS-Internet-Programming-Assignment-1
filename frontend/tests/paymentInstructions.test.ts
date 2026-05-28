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

  it("includes order reference and amount in every method block", () => {
    for (const { id } of PAYMENT_OPTIONS) {
      const blocks = paymentDetailBlocks(id, "SSP-000042", "A$99.00");
      const text = JSON.stringify(blocks);
      expect(text).toContain("SSP-000042");
      expect(text).toContain("A$99.00");
    }
  });

  it("uses translation keys when t is provided", () => {
    const t = (key: string) => `T:${key}`;
    const blocks = paymentDetailBlocks("pay_id", "REF", "A$10.00", t);
    expect(blocks[0]?.title).toContain("T:payDetail");
  });

  it("returns bank transfer demo details", () => {
    const blocks = paymentDetailBlocks("atm_transfer", "ORD-1", "A$50.00");
    const lines = blocks[0]?.lines ?? [];

    expect(blocks[0]?.title).toContain("Bank transfer");
    expect(lines.some((line) => line.label.includes("BSB"))).toBe(true);
    expect(lines.some((line) => line.value === "062-000")).toBe(true);
    expect(lines.some((line) => line.value === "ORD-1")).toBe(true);
  });

  it("returns PayID demo details with email type", () => {
    const blocks = paymentDetailBlocks("pay_id", "ORD-2", "A$25.00");
    const lines = blocks[0]?.lines ?? [];

    expect(blocks[0]?.title).toContain("PayID");
    expect(lines.some((line) => line.value.includes("@"))).toBe(true);
    expect(JSON.stringify(lines)).toContain("ORD-2");
  });

  it("returns BPAY demo details with numeric customer reference", () => {
    const blocks = paymentDetailBlocks("bpay", "SSP-000099", "A$120.00");
    const lines = blocks[0]?.lines ?? [];

    expect(blocks[0]?.title).toContain("BPAY");
    expect(lines.some((line) => line.label.includes("Biller"))).toBe(true);
    expect(lines.some((line) => line.value === "12345")).toBe(true);
    expect(lines.some((line) => line.value === "000099")).toBe(true);
  });

  it("falls back to a default BPAY customer reference when order id has no digits", () => {
    const blocks = paymentDetailBlocks("bpay", "ALPHA", "A$1.00");
    const customerRef = blocks[0]?.lines.find((line) =>
      line.label.includes("Customer reference"),
    );

    expect(customerRef?.value).toBe("0000000001");
  });
});
