import { cleanup, fireEvent, screen } from "@testing-library/react";
import { afterEach, beforeEach, describe, expect, it, vi } from "vitest";
import { LanguageMenu } from "@/components/LanguageMenu";
import { LOCALE_LABELS, LOCALE_STORAGE_KEY, SUPPORTED_LOCALES } from "@/lib/locales";
import { renderWithStorefrontProviders } from "./helpers/renderProviders";

vi.mock("@/lib/localeSync", async () => {
  const actual = await vi.importActual<typeof import("@/lib/localeSync")>(
    "@/lib/localeSync",
  );
  return {
    ...actual,
    fetchServerLocale: vi.fn(() => Promise.resolve(null)),
    pushServerLocale: vi.fn(() => Promise.resolve()),
  };
});

describe("LanguageMenu", () => {
  afterEach(() => {
    cleanup();
  });

  beforeEach(() => {
    window.localStorage.clear();
  });

  function openMenu(): void {
    fireEvent.click(screen.getByRole("button", { expanded: false }));
  }

  it("lists every supported locale", () => {
    renderWithStorefrontProviders(<LanguageMenu />);
    openMenu();

    for (const code of SUPPORTED_LOCALES) {
      expect(
        screen.getByRole("menuitemradio", { name: LOCALE_LABELS[code] }),
      ).toBeInTheDocument();
    }
  });

  it("persists Japanese when selected", () => {
    renderWithStorefrontProviders(<LanguageMenu />);
    openMenu();

    fireEvent.click(
      screen.getByRole("menuitemradio", { name: LOCALE_LABELS.ja }),
    );

    expect(window.localStorage.getItem(LOCALE_STORAGE_KEY)).toBe("ja");
    expect(screen.queryByRole("menu")).not.toBeInTheDocument();
  });

  it("marks the active locale as checked", () => {
    window.localStorage.setItem(LOCALE_STORAGE_KEY, "ko");
    renderWithStorefrontProviders(<LanguageMenu />);
    openMenu();

    expect(
      screen.getByRole("menuitemradio", { name: LOCALE_LABELS.ko }),
    ).toHaveAttribute("aria-checked", "true");
    expect(
      screen.getByRole("menuitemradio", { name: LOCALE_LABELS.en }),
    ).toHaveAttribute("aria-checked", "false");
  });
});
