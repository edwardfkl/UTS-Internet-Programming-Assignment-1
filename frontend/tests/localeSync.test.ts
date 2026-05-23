import { afterEach, beforeEach, describe, expect, it, vi } from "vitest";
import {
  fetchServerLocale,
  localeFromApi,
  localeToApi,
  pushServerLocale,
} from "@/lib/localeSync";
import {
  installFetchMock,
  jsonResponse,
  TEST_API_BASE,
} from "./helpers/fetchMock";

describe("localeSync", () => {
  beforeEach(() => {
    vi.stubEnv("NEXT_PUBLIC_API_URL", TEST_API_BASE);
  });

  afterEach(() => {
    vi.unstubAllGlobals();
  });

  it("localeToApi maps zh-TW to zh_TW", () => {
    expect(localeToApi("zh-TW")).toBe("zh_TW");
    expect(localeToApi("en")).toBe("en");
  });

  it("localeFromApi maps API values back", () => {
    expect(localeFromApi("zh_TW")).toBe("zh-TW");
    expect(localeFromApi("ja")).toBe("ja");
    expect(localeFromApi("fr")).toBeNull();
  });

  it("fetchServerLocale returns null on HTTP error", async () => {
    installFetchMock(() => jsonResponse({}, 500));
    await expect(fetchServerLocale()).resolves.toBeNull();
  });

  it("fetchServerLocale parses a supported locale", async () => {
    installFetchMock((url) => {
      if (url.endsWith("/api/locale")) {
        return jsonResponse({ locale: "ko" });
      }
      return jsonResponse({}, 404);
    });
    await expect(fetchServerLocale()).resolves.toBe("ko");
  });

  it("pushServerLocale POSTs the mapped locale with credentials", async () => {
    const fetchMock = installFetchMock((url, init) => {
      if (url.endsWith("/api/locale") && init?.method === "POST") {
        expect(JSON.parse(String(init.body))).toEqual({ locale: "zh_TW" });
        return jsonResponse({ ok: true });
      }
      return jsonResponse({}, 404);
    });

    await pushServerLocale("zh-TW");
    expect(fetchMock).toHaveBeenCalledOnce();
  });
});
