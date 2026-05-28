import { describe, expect, it } from "vitest";
import {
  DEFAULT_LOCALE,
  LOCALE_LABELS,
  LOCALE_STORAGE_KEY,
  SUPPORTED_LOCALES,
  type AppLocale,
} from "@/lib/locales";

describe("locales config", () => {
  it("defaults to English", () => {
    expect(DEFAULT_LOCALE).toBe("en");
  });

  it("lists four storefront locales", () => {
    expect(SUPPORTED_LOCALES).toEqual(["en", "zh-TW", "ja", "ko"]);
  });

  it("uses a stable localStorage key", () => {
    expect(LOCALE_STORAGE_KEY).toBe("edward_store_locale");
  });

  it("defines a human label for every supported locale", () => {
    for (const code of SUPPORTED_LOCALES) {
      expect(LOCALE_LABELS[code].length).toBeGreaterThan(0);
    }
  });

  it("includes Traditional Chinese and Japanese labels", () => {
    expect(LOCALE_LABELS["zh-TW"]).toBe("繁體中文");
    expect(LOCALE_LABELS.ja).toBe("日本語");
  });

  it.each(SUPPORTED_LOCALES satisfies AppLocale[])(
    "covers %s in the label map",
    (code) => {
      expect(Object.keys(LOCALE_LABELS)).toContain(code);
    },
  );
});
