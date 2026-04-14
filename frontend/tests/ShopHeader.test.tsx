import { render, screen } from "@testing-library/react";
import { ShopHeader } from "@/components/ShopHeader";
import { LocaleProvider } from "@/contexts/locale-context";
import { beforeEach, describe, expect, it, vi } from "vitest";

vi.mock("next/link", () => ({
  default: function MockLink({
    children,
    href,
  }: {
    children: React.ReactNode;
    href: string;
  }) {
    return <a href={href}>{children}</a>;
  },
}));

vi.mock("@/contexts/auth-context", () => ({
  useAuth: () => ({
    user: null,
    ready: true,
    login: vi.fn(),
    register: vi.fn(),
    logout: vi.fn(),
    refreshUser: vi.fn(),
  }),
}));

vi.mock("@/lib/localeSync", async () => {
  const actual = await vi.importActual<typeof import("@/lib/localeSync")>("@/lib/localeSync");
  return {
    ...actual,
    fetchServerLocale: vi.fn(() => Promise.resolve(null)),
    pushServerLocale: vi.fn(() => Promise.resolve()),
  };
});

describe("ShopHeader", () => {
  beforeEach(() => {
    window.localStorage.clear();
  });

  it("renders store title and log in when logged out", () => {
    render(
      <LocaleProvider>
        <ShopHeader />
      </LocaleProvider>,
    );

    expect(screen.getByRole("heading", { name: /Edward's Store/i })).toBeInTheDocument();
    expect(screen.getByRole("link", { name: /^catalog$/i })).toHaveAttribute("href", "/");
    expect(screen.getByRole("link", { name: /^log in$/i })).toHaveAttribute("href", "/login");
  });

  it("shows tagline when provided", () => {
    render(
      <LocaleProvider>
        <ShopHeader tagline="Checkout flow" />
      </LocaleProvider>,
    );

    expect(screen.getByText("Checkout flow")).toBeInTheDocument();
  });
});
