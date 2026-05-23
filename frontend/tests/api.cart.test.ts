import { afterEach, beforeEach, describe, expect, it, vi } from "vitest";
import { setAuthToken } from "@/lib/authToken";
import {
  addCartItem,
  clearStoredCartToken,
  createCartSession,
  deleteCartItem,
  ensureCartToken,
  fetchCart,
  getStoredCartToken,
  placeOrder,
  resetCartSession,
  setStoredCartToken,
  updateCartItem,
} from "@/lib/api";
import type { ShippingForm } from "@/lib/types";

const API_BASE = "http://test-api.local";
const STALE_TOKEN = "00000000-0000-4000-8000-000000000001";
const NEW_TOKEN = "00000000-0000-4000-8000-000000000099";
const AUTH_TOKEN = "test-bearer-token";

const emptyCart = { status: "cart", items: [], total: 0 };

const shipping: ShippingForm = {
  recipient_name: "Ada Lovelace",
  phone: "+61 400 000 000",
  line1: "1 Test St",
  line2: "",
  city: "Sydney",
  state: "NSW",
  postcode: "2000",
  country: "AU",
};

function jsonResponse(body: unknown, status = 200): Response {
  return Response.json(body, { status });
}

function noContentResponse(): Response {
  return new Response(null, { status: 204 });
}

function cartTokenHeader(init?: RequestInit): string | undefined {
  const headers = init?.headers as Record<string, string> | undefined;
  return headers?.["X-Cart-Token"];
}

function authHeader(init?: RequestInit): string | undefined {
  const headers = init?.headers as Record<string, string> | undefined;
  return headers?.Authorization;
}

type FetchHandler = (
  url: string,
  init?: RequestInit,
) => Response | Promise<Response>;

function installFetchMock(handler: FetchHandler) {
  const fetchMock = vi.fn((input: RequestInfo | URL, init?: RequestInit) => {
    const url = typeof input === "string" ? input : input.toString();
    return Promise.resolve(handler(url, init));
  });
  vi.stubGlobal("fetch", fetchMock);
  return fetchMock;
}

/** Default stale-token flow: cart op fails once, session succeeds, cart op succeeds. */
function staleThenRecoverCartHandler(
  cartMatcher: (url: string, method: string) => boolean,
  onCartSuccess: (url: string, init?: RequestInit) => Response,
): FetchHandler {
  let cartAttempts = 0;

  return (url, init) => {
    const method = init?.method ?? "GET";

    if (url.endsWith("/api/cart/sessions") && method === "POST") {
      return jsonResponse({ token: NEW_TOKEN }, 201);
    }

    if (cartMatcher(url, method)) {
      cartAttempts += 1;
      if (cartAttempts === 1) {
        return jsonResponse({ message: "Order not found" }, 404);
      }
      return onCartSuccess(url, init);
    }

    return jsonResponse({ message: `Unexpected request ${method} ${url}` }, 500);
  };
}

describe("cart token storage", () => {
  beforeEach(() => {
    window.localStorage.clear();
    vi.unstubAllGlobals();
  });

  it("reads and writes the cart token in localStorage", () => {
    expect(getStoredCartToken()).toBeNull();
    setStoredCartToken(STALE_TOKEN);
    expect(getStoredCartToken()).toBe(STALE_TOKEN);
    clearStoredCartToken();
    expect(getStoredCartToken()).toBeNull();
  });

  it("createCartSession stores the server token", async () => {
    installFetchMock((url, init) => {
      if (url.endsWith("/api/cart/sessions") && init?.method === "POST") {
        return jsonResponse({ token: NEW_TOKEN }, 201);
      }
      return jsonResponse({}, 500);
    });

    await expect(createCartSession()).resolves.toBe(NEW_TOKEN);
    expect(getStoredCartToken()).toBe(NEW_TOKEN);
  });

  it("ensureCartToken creates a session when storage is empty", async () => {
    installFetchMock((url, init) => {
      if (url.endsWith("/api/cart/sessions") && init?.method === "POST") {
        return jsonResponse({ token: NEW_TOKEN }, 201);
      }
      return jsonResponse({}, 500);
    });

    await expect(ensureCartToken()).resolves.toBe(NEW_TOKEN);
  });

  it("ensureCartToken returns the stored token without calling the API", async () => {
    setStoredCartToken(STALE_TOKEN);
    const fetchMock = installFetchMock(() => jsonResponse({}, 500));

    await expect(ensureCartToken()).resolves.toBe(STALE_TOKEN);
    expect(fetchMock).not.toHaveBeenCalled();
  });

  it("resetCartSession clears storage then creates a new session", async () => {
    setStoredCartToken(STALE_TOKEN);
    installFetchMock((url, init) => {
      if (url.endsWith("/api/cart/sessions") && init?.method === "POST") {
        return jsonResponse({ token: NEW_TOKEN }, 201);
      }
      return jsonResponse({}, 500);
    });

    await expect(resetCartSession()).resolves.toBe(NEW_TOKEN);
    expect(getStoredCartToken()).toBe(NEW_TOKEN);
  });
});

describe("stale cart token recovery (fetchCartApi)", () => {
  beforeEach(() => {
    window.localStorage.clear();
    setStoredCartToken(STALE_TOKEN);
    vi.stubEnv("NEXT_PUBLIC_API_URL", API_BASE);
  });

  afterEach(() => {
    vi.unstubAllGlobals();
  });

  it.each([404, 401, 403] as const)(
    "fetchCart recovers after HTTP %s and returns the new cart",
    async (staleStatus) => {
      let cartReads = 0;
      const fetchMock = installFetchMock((url, init) => {
        const method = init?.method ?? "GET";

        if (url.endsWith("/api/cart/sessions") && method === "POST") {
          return jsonResponse({ token: NEW_TOKEN }, 201);
        }

        if (url.endsWith("/api/cart") && method === "GET") {
          cartReads += 1;
          if (cartReads === 1) {
            return jsonResponse({ message: "Order not found" }, staleStatus);
          }
          return jsonResponse(emptyCart);
        }

        return jsonResponse({ message: `Unexpected ${method} ${url}` }, 500);
      });

      await expect(fetchCart(STALE_TOKEN)).resolves.toEqual(emptyCart);
      expect(getStoredCartToken()).toBe(NEW_TOKEN);

      const cartCalls = fetchMock.mock.calls.filter(([url]) =>
        String(url).endsWith("/api/cart"),
      );
      expect(cartCalls).toHaveLength(2);
      expect(cartTokenHeader(cartCalls[0][1])).toBe(STALE_TOKEN);
      expect(cartTokenHeader(cartCalls[1][1])).toBe(NEW_TOKEN);
      expect(
        fetchMock.mock.calls.some(([url]) => String(url).endsWith("/api/cart/sessions")),
      ).toBe(true);
    },
  );

  it("addCartItem recovers after a deleted order (404) and succeeds on retry", async () => {
    const fetchMock = installFetchMock(
      staleThenRecoverCartHandler(
        (url, method) => url.endsWith("/api/cart/items") && method === "POST",
        () => jsonResponse({ id: 1 }, 201),
      ),
    );

    await expect(addCartItem(STALE_TOKEN, 42, 2)).resolves.toBeUndefined();
    expect(getStoredCartToken()).toBe(NEW_TOKEN);

    const addCalls = fetchMock.mock.calls.filter(([url, init]) => {
      return String(url).endsWith("/api/cart/items") && init?.method === "POST";
    });
    expect(addCalls).toHaveLength(2);
    expect(cartTokenHeader(addCalls[0][1])).toBe(STALE_TOKEN);
    expect(cartTokenHeader(addCalls[1][1])).toBe(NEW_TOKEN);

    const firstBody = JSON.parse(String(addCalls[0][1]?.body));
    const secondBody = JSON.parse(String(addCalls[1][1]?.body));
    expect(firstBody).toEqual({ product_id: 42, quantity: 2 });
    expect(secondBody).toEqual(firstBody);
  });

  it("updateCartItem recovers after stale token and retries PATCH", async () => {
    const fetchMock = installFetchMock(
      staleThenRecoverCartHandler(
        (url, method) => url.includes("/api/cart/items/7") && method === "PATCH",
        () => jsonResponse({ id: 7, quantity: 3 }),
      ),
    );

    await expect(updateCartItem(STALE_TOKEN, 7, 3)).resolves.toBeUndefined();
    expect(getStoredCartToken()).toBe(NEW_TOKEN);

    const patchCalls = fetchMock.mock.calls.filter(
      ([, init]) => init?.method === "PATCH",
    );
    expect(patchCalls).toHaveLength(2);
    expect(cartTokenHeader(patchCalls[1][1])).toBe(NEW_TOKEN);
  });

  it("deleteCartItem recovers after stale token and retries DELETE", async () => {
    const fetchMock = installFetchMock(
      staleThenRecoverCartHandler(
        (url, method) => url.includes("/api/cart/items/9") && method === "DELETE",
        () => noContentResponse(),
      ),
    );

    await expect(deleteCartItem(STALE_TOKEN, 9)).resolves.toBeUndefined();
    expect(getStoredCartToken()).toBe(NEW_TOKEN);
  });

  it("placeOrder recovers after stale token and retries checkout", async () => {
    const checkoutResult = {
      order_reference: "ORD-1",
      order_token: "ord-token",
      status: "pending",
      payment_method: "card",
      placed_at: "2026-05-24T00:00:00Z",
      promo_code: null,
      discount_amount: 0,
      subtotal_amount: 10,
      total_amount: 10,
      total: 10,
      lines: [],
      shipping: {
        recipient_name: shipping.recipient_name,
        phone: shipping.phone,
        line1: shipping.line1,
        line2: null,
        city: shipping.city,
        state: shipping.state,
        postcode: shipping.postcode,
        country: shipping.country,
      },
    };

    installFetchMock(
      staleThenRecoverCartHandler(
        (url, method) => url.endsWith("/api/checkout") && method === "POST",
        () => jsonResponse(checkoutResult),
      ),
    );

    await expect(
      placeOrder(STALE_TOKEN, "card", shipping, false, null),
    ).resolves.toEqual(checkoutResult);
    expect(getStoredCartToken()).toBe(NEW_TOKEN);
  });

  it("end-to-end: stored stale token + addCartItem updates storage without refresh", async () => {
    installFetchMock(
      staleThenRecoverCartHandler(
        (url, method) => url.endsWith("/api/cart/items") && method === "POST",
        () => jsonResponse({}, 201),
      ),
    );

    const token = await ensureCartToken();
    expect(token).toBe(STALE_TOKEN);

    await addCartItem(token, 1, 1);
    expect(getStoredCartToken()).toBe(NEW_TOKEN);
  });

  it("sends Authorization when the user is logged in", async () => {
    setAuthToken(AUTH_TOKEN);
    installFetchMock((url, init) => {
      if (url.endsWith("/api/cart/sessions") && init?.method === "POST") {
        expect(authHeader(init)).toBe(`Bearer ${AUTH_TOKEN}`);
        return jsonResponse({ token: NEW_TOKEN }, 201);
      }
      if (url.endsWith("/api/cart/items") && init?.method === "POST") {
        expect(authHeader(init)).toBe(`Bearer ${AUTH_TOKEN}`);
        if (cartTokenHeader(init) === STALE_TOKEN) {
          return jsonResponse({ message: "Order not found" }, 404);
        }
        return jsonResponse({}, 201);
      }
      return jsonResponse({}, 500);
    });

    await addCartItem(STALE_TOKEN, 5, 1);
  });
});

describe("stale cart token recovery — no spurious retry", () => {
  beforeEach(() => {
    window.localStorage.clear();
    setStoredCartToken(STALE_TOKEN);
    vi.stubEnv("NEXT_PUBLIC_API_URL", API_BASE);
  });

  afterEach(() => {
    vi.unstubAllGlobals();
  });

  it("fetchCart succeeds on the first request without creating a session", async () => {
    const fetchMock = installFetchMock((url) => {
      if (url.endsWith("/api/cart")) {
        return jsonResponse(emptyCart);
      }
      return jsonResponse({}, 500);
    });

    await expect(fetchCart(STALE_TOKEN)).resolves.toEqual(emptyCart);
    expect(fetchMock).toHaveBeenCalledTimes(1);
    expect(
      fetchMock.mock.calls.some(([url]) => String(url).endsWith("/api/cart/sessions")),
    ).toBe(false);
  });

  it("fetchCart does not recover from HTTP 500", async () => {
    const fetchMock = installFetchMock((url) => {
      if (url.endsWith("/api/cart")) {
        return jsonResponse({ message: "Server error" }, 500);
      }
      return jsonResponse({ token: NEW_TOKEN }, 201);
    });

    await expect(fetchCart(STALE_TOKEN)).rejects.toThrow(
      "Could not load cart (HTTP 500)",
    );
    expect(
      fetchMock.mock.calls.some(([url]) => String(url).endsWith("/api/cart/sessions")),
    ).toBe(false);
  });

  it("addCartItem does not retry on validation errors (422)", async () => {
    const fetchMock = installFetchMock((url, init) => {
      if (url.endsWith("/api/cart/items") && init?.method === "POST") {
        return jsonResponse(
          { errors: { quantity: ["Not enough stock for this product."] } },
          422,
        );
      }
      return jsonResponse({ token: NEW_TOKEN }, 201);
    });

    await expect(addCartItem(STALE_TOKEN, 1, 99)).rejects.toThrow(
      "Not enough stock for this product.",
    );
    expect(fetchMock).toHaveBeenCalledTimes(1);
  });

  it("addCartItem retries only once when the cart stays missing", async () => {
    const fetchMock = installFetchMock((url, init) => {
      if (url.endsWith("/api/cart/sessions") && init?.method === "POST") {
        return jsonResponse({ token: NEW_TOKEN }, 201);
      }
      if (url.endsWith("/api/cart/items") && init?.method === "POST") {
        return jsonResponse({ message: "Order not found" }, 404);
      }
      return jsonResponse({}, 500);
    });

    await expect(addCartItem(STALE_TOKEN, 1, 1)).rejects.toThrow("Order not found");

    const addCalls = fetchMock.mock.calls.filter(
      ([url, init]) => String(url).endsWith("/api/cart/items") && init?.method === "POST",
    );
    expect(addCalls).toHaveLength(2);
    expect(
      fetchMock.mock.calls.filter(([url]) => String(url).endsWith("/api/cart/sessions")),
    ).toHaveLength(1);
  });

  it("updateCartItem surfaces API validation messages without session recovery", async () => {
    installFetchMock((url, init) => {
      if (url.includes("/api/cart/items/3") && init?.method === "PATCH") {
        return jsonResponse(
          { errors: { quantity: ["Not enough stock for this product."] } },
          422,
        );
      }
      return jsonResponse({}, 500);
    });

    await expect(updateCartItem(STALE_TOKEN, 3, 50)).rejects.toThrow(
      "Not enough stock for this product.",
    );
  });

  it("deleteCartItem throws when retry still fails", async () => {
    installFetchMock((url, init) => {
      if (url.endsWith("/api/cart/sessions") && init?.method === "POST") {
        return jsonResponse({ token: NEW_TOKEN }, 201);
      }
      if (url.includes("/api/cart/items/4") && init?.method === "DELETE") {
        return jsonResponse({ message: "Not found" }, 404);
      }
      return jsonResponse({}, 500);
    });

    await expect(deleteCartItem(STALE_TOKEN, 4)).rejects.toThrow(
      "Could not remove item (HTTP 404)",
    );
  });
});

describe("createCartSession errors", () => {
  beforeEach(() => {
    window.localStorage.clear();
    vi.stubEnv("NEXT_PUBLIC_API_URL", API_BASE);
  });

  afterEach(() => {
    vi.unstubAllGlobals();
  });

  it("throws when the session endpoint fails", async () => {
    installFetchMock((url, init) => {
      if (url.endsWith("/api/cart/sessions") && init?.method === "POST") {
        return jsonResponse({ message: "Unavailable" }, 503);
      }
      return jsonResponse({}, 500);
    });

    await expect(createCartSession()).rejects.toThrow(
      "Could not create a cart (HTTP 503)",
    );
    expect(getStoredCartToken()).toBeNull();
  });
});
