export const SUPPORTED_CURRENCIES = [
  "HKD",
  "USD",
  "EUR",
  "GBP",
  "AUD",
  "JPY",
  "CNY",
  "TWD",
  "KRW",
] as const;

export type AppCurrency = (typeof SUPPORTED_CURRENCIES)[number];

export const DEFAULT_CURRENCY: AppCurrency = "HKD";

export const CURRENCY_STORAGE_KEY = "edward_store_currency";

/** Multiply a HKD amount by this rate to get the display currency amount. */
export const EXCHANGE_RATES_FROM_HKD: Record<AppCurrency, number> = {
  HKD: 1,
  USD: 0.128,
  EUR: 0.118,
  GBP: 0.101,
  AUD: 0.196,
  JPY: 19.5,
  CNY: 0.92,
  TWD: 4.1,
  KRW: 185,
};

export const CURRENCY_LABELS: Record<AppCurrency, string> = {
  HKD: "HK$ · HKD",
  USD: "US$ · USD",
  EUR: "€ · EUR",
  GBP: "£ · GBP",
  AUD: "A$ · AUD",
  JPY: "¥ · JPY",
  CNY: "¥ · CNY",
  TWD: "NT$ · TWD",
  KRW: "₩ · KRW",
};

/** Locale hint for Intl.NumberFormat per currency. */
export const CURRENCY_INTL_LOCALE: Record<AppCurrency, string> = {
  HKD: "zh-HK",
  USD: "en-US",
  EUR: "de-DE",
  GBP: "en-GB",
  AUD: "en-AU",
  JPY: "ja-JP",
  CNY: "zh-CN",
  TWD: "zh-TW",
  KRW: "ko-KR",
};
