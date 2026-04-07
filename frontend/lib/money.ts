import type { Product } from "./types";

export const money = new Intl.NumberFormat("en", {
  style: "currency",
  currency: "HKD",
});

export function parsePrice(p: Pick<Product, "price">): number {
  return Number.parseFloat(p.price);
}
