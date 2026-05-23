import { describe, expect, it } from "vitest";
import { resolveMessage } from "@/lib/i18n-resolve";

const messages = {
  cart: {
    title: "Your cart",
    empty: "Cart is empty",
  },
  common: {
    remove: "Remove",
  },
};

describe("resolveMessage", () => {
  it("resolves nested keys", () => {
    expect(resolveMessage(messages, "cart.title")).toBe("Your cart");
    expect(resolveMessage(messages, "common.remove")).toBe("Remove");
  });

  it("returns the path when a key is missing", () => {
    expect(resolveMessage(messages, "cart.unknown")).toBe("cart.unknown");
    expect(resolveMessage(messages, "missing")).toBe("missing");
  });

  it("returns the path when traversal hits a string early", () => {
    expect(resolveMessage(messages, "cart.title.extra")).toBe("cart.title.extra");
  });
});
