import { cleanup, fireEvent, screen } from "@testing-library/react";
import { afterEach, beforeEach, describe, expect, it, vi } from "vitest";
import { CurrencyMenu } from "@/components/CurrencyMenu";
import {
  CURRENCY_LABELS,
  CURRENCY_STORAGE_KEY,
  SUPPORTED_CURRENCIES,
} from "@/lib/currencies";
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

describe("CurrencyMenu", () => {
  afterEach(() => {
    cleanup();
  });

  beforeEach(() => {
    window.localStorage.clear();
  });

  function openMenu(): HTMLElement {
    const trigger = screen.getByTitle("Currency");
    fireEvent.click(trigger);
    return trigger;
  }

  it("shows AUD by default on the trigger button", () => {
    renderWithStorefrontProviders(<CurrencyMenu />);
    expect(screen.getByTitle("Currency")).toHaveTextContent("AUD");
  });

  it("lists every supported currency including HKD", () => {
    renderWithStorefrontProviders(<CurrencyMenu />);
    openMenu();

    for (const code of SUPPORTED_CURRENCIES) {
      expect(
        screen.getByRole("menuitemradio", { name: CURRENCY_LABELS[code] }),
      ).toBeInTheDocument();
    }
    expect(
      screen.getByRole("menuitemradio", { name: CURRENCY_LABELS.HKD }),
    ).toHaveTextContent("HK$ · HKD");
  });

  it("persists HKD when selected from the menu", () => {
    renderWithStorefrontProviders(<CurrencyMenu />);
    openMenu();

    fireEvent.click(
      screen.getByRole("menuitemradio", { name: CURRENCY_LABELS.HKD }),
    );

    expect(screen.getByTitle("Currency")).toHaveTextContent("HKD");
    expect(window.localStorage.getItem(CURRENCY_STORAGE_KEY)).toBe("HKD");
  });

  it("marks the active currency as checked", () => {
    window.localStorage.setItem(CURRENCY_STORAGE_KEY, "EUR");
    renderWithStorefrontProviders(<CurrencyMenu />);
    openMenu();

    expect(
      screen.getByRole("menuitemradio", { name: CURRENCY_LABELS.EUR }),
    ).toHaveAttribute("aria-checked", "true");
    expect(
      screen.getByRole("menuitemradio", { name: CURRENCY_LABELS.HKD }),
    ).toHaveAttribute("aria-checked", "false");
  });

  it("closes the menu after selecting a currency", () => {
    renderWithStorefrontProviders(<CurrencyMenu />);
    openMenu();

    fireEvent.click(
      screen.getByRole("menuitemradio", { name: CURRENCY_LABELS.USD }),
    );

    expect(screen.queryByRole("menu")).not.toBeInTheDocument();
    expect(screen.getByTitle("Currency")).toHaveTextContent("USD");
  });
});
