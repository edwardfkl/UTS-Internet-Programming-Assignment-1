import type { Product } from "./types";
import {
  CURRENCY_INTL_LOCALE,
  EXCHANGE_RATES_FROM_AUD,
  type AppCurrency,
} from "./currencies";

export function parsePrice(p: Pick<Product, "price">): number {
  return Number.parseFloat(p.price);
}

export function convertFromAud(
  amountAud: number,
  currency: AppCurrency,
): number {
  return amountAud * EXCHANGE_RATES_FROM_AUD[currency];
}

export function createMoneyFormatter(currency: AppCurrency): Intl.NumberFormat {
  return new Intl.NumberFormat(CURRENCY_INTL_LOCALE[currency], {
    style: "currency",
    currency,
  });
}

/** Format a catalogue/order amount stored in AUD for display in another currency. */
export function formatMoney(
  amountAud: number,
  currency: AppCurrency = "AUD",
): string {
  return createMoneyFormatter(currency).format(
    convertFromAud(amountAud, currency),
  );
}

/** @deprecated Use formatMoney(amount, currency) or useCurrency().formatMoney instead. */
export const money = createMoneyFormatter("AUD");
