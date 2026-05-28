import { describe, expect, it } from "vitest";
import { resolveMessage } from "@/lib/i18n-resolve";

const messages = {
  cart: {
    title: "Your cart",
    empty: "Cart is empty",
    nested: {
      label: "Nested label",
    },
  },
  common: {
    remove: "Remove",
  },
};

describe("resolveMessage", () => {
  it("resolves nested keys", () => {
    expect(resolveMessage(messages, "cart.title")).toBe("Your cart");
    expect(resolveMessage(messages, "common.remove")).toBe("Remove");
    expect(resolveMessage(messages, "cart.nested.label")).toBe("Nested label");
  });

  it("returns the path when a key is missing", () => {
    expect(resolveMessage(messages, "cart.unknown")).toBe("cart.unknown");
    expect(resolveMessage(messages, "missing")).toBe("missing");
  });

  it("returns the path when traversal hits a string early", () => {
    expect(resolveMessage(messages, "cart.title.extra")).toBe("cart.title.extra");
  });

  it("returns the path when the resolved node is not a string", () => {
    expect(resolveMessage(messages, "cart.nested")).toBe("cart.nested");
  });

  it("ignores empty path segments", () => {
    expect(resolveMessage(messages, "cart..title")).toBe("Your cart");
  });
});
