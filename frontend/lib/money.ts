import type { Product } from "./types";
import {
  CURRENCY_INTL_LOCALE,
  EXCHANGE_RATES_FROM_HKD,
  type AppCurrency,
} from "./currencies";

export function parsePrice(p: Pick<Product, "price">): number {
  return Number.parseFloat(p.price);
}

export function convertFromHkd(
  amountHkd: number,
  currency: AppCurrency,
): number {
  return amountHkd * EXCHANGE_RATES_FROM_HKD[currency];
}

export function createMoneyFormatter(currency: AppCurrency): Intl.NumberFormat {
  return new Intl.NumberFormat(CURRENCY_INTL_LOCALE[currency], {
    style: "currency",
    currency,
  });
}

/** Format a catalogue/order amount stored in HKD for display in another currency. */
export function formatMoney(
  amountHkd: number,
  currency: AppCurrency = "HKD",
): string {
  return createMoneyFormatter(currency).format(
    convertFromHkd(amountHkd, currency),
  );
}

/** @deprecated Use formatMoney(amount, currency) or useCurrency().formatMoney instead. */
export const money = createMoneyFormatter("HKD");
