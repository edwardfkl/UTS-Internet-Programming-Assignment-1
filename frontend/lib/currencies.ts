export const SUPPORTED_CURRENCIES = [
  "AUD",
  "HKD",
  "USD",
  "EUR",
  "GBP",
  "JPY",
  "CNY",
  "TWD",
  "KRW",
] as const;

export type AppCurrency = (typeof SUPPORTED_CURRENCIES)[number];

export const DEFAULT_CURRENCY: AppCurrency = "AUD";

export const CURRENCY_STORAGE_KEY = "edward_store_currency";

/** Multiply an AUD amount by this rate to get the display currency amount. */
export const EXCHANGE_RATES_FROM_AUD: Record<AppCurrency, number> = {
  AUD: 1,
  HKD: 5.102,
  USD: 0.653,
  EUR: 0.602,
  GBP: 0.515,
  JPY: 99.5,
  CNY: 4.69,
  TWD: 20.9,
  KRW: 944,
};

export const CURRENCY_LABELS: Record<AppCurrency, string> = {
  AUD: "A$ · AUD",
  HKD: "HK$ · HKD",
  USD: "US$ · USD",
  EUR: "€ · EUR",
  GBP: "£ · GBP",
  JPY: "¥ · JPY",
  CNY: "¥ · CNY",
  TWD: "NT$ · TWD",
  KRW: "₩ · KRW",
};

/** Locale hint for Intl.NumberFormat per currency. */
export const CURRENCY_INTL_LOCALE: Record<AppCurrency, string> = {
  AUD: "en-AU",
  HKD: "zh-HK",
  USD: "en-US",
  EUR: "de-DE",
  GBP: "en-GB",
  JPY: "ja-JP",
  CNY: "zh-CN",
  TWD: "zh-TW",
  KRW: "ko-KR",
};
