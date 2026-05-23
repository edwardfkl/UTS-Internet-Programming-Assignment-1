import { cleanup, fireEvent, screen } from "@testing-library/react";
import { afterEach, describe, expect, it, vi } from "vitest";
import { CartPanel } from "@/components/CartPanel";
import { renderWithStorefrontProviders } from "./helpers/renderProviders";
import type { CartLine } from "@/lib/types";

vi.mock("next/image", () => ({
  default: function MockImage() {
    return null;
  },
}));

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

const authMock = vi.hoisted(() => ({
  user: null as {
    id: number;
    name: string;
    email: string;
    avatar_url: string | null;
    is_admin: boolean;
  } | null,
}));

vi.mock("@/contexts/auth-context", () => ({
  useAuth: () => ({
    user: authMock.user,
    ready: true,
    login: vi.fn(),
    register: vi.fn(),
    logout: vi.fn(),
    refreshUser: vi.fn(),
  }),
}));

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

const line: CartLine = {
  id: 1,
  quantity: 2,
  line_total: 50,
  product: {
    id: 9,
    name: "Notebook",
    description: null,
    price: "25.00",
    image_url: null,
    stock: 10,
  },
};

describe("CartPanel", () => {
  afterEach(() => {
    cleanup();
    authMock.user = null;
  });

  it("shows loading state", () => {
    renderWithStorefrontProviders(
      <CartPanel
        lines={[]}
        total={0}
        loading
        error={null}
        busyId={null}
        cartStatus="cart"
        onStartNewCart={vi.fn()}
        onQtyChange={vi.fn()}
        onRemove={vi.fn()}
      />,
    );
    expect(screen.getByText(/loading/i)).toBeInTheDocument();
  });

  it("shows error alert", () => {
    renderWithStorefrontProviders(
      <CartPanel
        lines={[]}
        total={0}
        loading={false}
        error="Order not found"
        busyId={null}
        cartStatus="cart"
        onStartNewCart={vi.fn()}
        onQtyChange={vi.fn()}
        onRemove={vi.fn()}
      />,
    );
    expect(screen.getByRole("alert")).toHaveTextContent("Order not found");
  });

  it("renders lines, total, and checkout link for signed-in user", () => {
    authMock.user = {
      id: 1,
      name: "Ada",
      email: "ada@example.com",
      avatar_url: null,
      is_admin: false,
    };
    renderWithStorefrontProviders(
      <CartPanel
        lines={[line]}
        total={50}
        loading={false}
        error={null}
        busyId={null}
        cartStatus="cart"
        onStartNewCart={vi.fn()}
        onQtyChange={vi.fn()}
        onRemove={vi.fn()}
      />,
    );

    expect(screen.getByText("Notebook")).toBeInTheDocument();
    expect(screen.getByRole("link", { name: /checkout/i })).toHaveAttribute(
      "href",
      "/checkout",
    );
  });

  it("prompts login for checkout when guest", () => {
    renderWithStorefrontProviders(
      <CartPanel
        lines={[line]}
        total={50}
        loading={false}
        error={null}
        busyId={null}
        cartStatus="cart"
        onStartNewCart={vi.fn()}
        onQtyChange={vi.fn()}
        onRemove={vi.fn()}
      />,
    );

    const loginLinks = screen.getAllByRole("link", { name: /log in to checkout/i });
    expect(loginLinks[0]).toHaveAttribute("href", "/login?redirect=%2Fcheckout");
  });

  it("calls onRemove when remove is clicked", () => {
    const onRemove = vi.fn();
    renderWithStorefrontProviders(
      <CartPanel
        lines={[line]}
        total={50}
        loading={false}
        error={null}
        busyId={null}
        cartStatus="cart"
        onStartNewCart={vi.fn()}
        onQtyChange={vi.fn()}
        onRemove={onRemove}
      />,
    );

    fireEvent.click(screen.getAllByRole("button", { name: /^remove$/i })[0]);
    expect(onRemove).toHaveBeenCalledWith(1);
  });

  it("shows submitted-cart banner when status is not cart", () => {
    renderWithStorefrontProviders(
      <CartPanel
        lines={[line]}
        total={50}
        loading={false}
        error={null}
        busyId={null}
        cartStatus="pending_payment"
        onStartNewCart={vi.fn()}
        onQtyChange={vi.fn()}
        onRemove={vi.fn()}
      />,
    );

    expect(screen.queryByRole("link", { name: /checkout/i })).not.toBeInTheDocument();
    expect(screen.getByRole("button", { name: /start new cart/i })).toBeInTheDocument();
  });
});
