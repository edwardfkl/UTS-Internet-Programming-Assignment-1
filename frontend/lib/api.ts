import type {
  CartResponse,
  CheckoutResult,
  PaymentMethod,
  Product,
  ShippingForm,
} from "./types";
import { getAuthToken } from "./authToken";

function bearerHeaders(): Record<string, string> {
  const t = getAuthToken();
  return t ? { Authorization: `Bearer ${t}` } : {};
}

const CART_STORAGE_KEY = "assignment1_cart_token";
/** Use `/?cart=<order-token>` to restore a cart bookmarked on another browser. */
export const CART_URL_QUERY = "cart";

/** Laravel `Str::uuid()` (UUID v4) */
const UUID_RE =
  /^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i;

export function applyCartFromUrlIfPresent(): void {
  if (typeof window === "undefined") return;
  const params = new URLSearchParams(window.location.search);
  const fromQuery = params.get(CART_URL_QUERY)?.trim();
  if (!fromQuery || !UUID_RE.test(fromQuery)) return;
  window.localStorage.setItem(CART_STORAGE_KEY, fromQuery);
  params.delete(CART_URL_QUERY);
  const search = params.toString();
  const next = `${window.location.pathname}${search ? `?${search}` : ""}${window.location.hash}`;
  window.history.replaceState({}, "", next);
}

export function apiBase(): string {
  return process.env.NEXT_PUBLIC_API_URL ?? "http://127.0.0.1:8000";
}

export function getStoredCartToken(): string | null {
  if (typeof window === "undefined") return null;
  return window.localStorage.getItem(CART_STORAGE_KEY);
}

export function setStoredCartToken(token: string): void {
  window.localStorage.setItem(CART_STORAGE_KEY, token);
}

export function clearStoredCartToken(): void {
  if (typeof window === "undefined") return;
  window.localStorage.removeItem(CART_STORAGE_KEY);
}

/** Clears the browser token and opens a new draft order on the server. */
export async function resetCartSession(): Promise<string> {
  clearStoredCartToken();
  return createCartSession();
}

export async function createCartSession(): Promise<string> {
  const res = await fetch(`${apiBase()}/api/cart/sessions`, {
    method: "POST",
    headers: { Accept: "application/json", ...bearerHeaders() },
  });
  if (!res.ok) {
    throw new Error(`Could not create a cart (HTTP ${res.status})`);
  }
  const data = (await res.json()) as { token: string };
  setStoredCartToken(data.token);
  return data.token;
}

export async function ensureCartToken(): Promise<string> {
  applyCartFromUrlIfPresent();
  const existing = getStoredCartToken();
  if (existing) return existing;
  return createCartSession();
}

export async function fetchProducts(): Promise<Product[]> {
  const res = await fetch(`${apiBase()}/api/products`, {
    headers: { Accept: "application/json" },
    cache: "no-store",
  });
  if (!res.ok) {
    throw new Error(`Could not load products (HTTP ${res.status})`);
  }
  return res.json() as Promise<Product[]>;
}

export async function fetchProduct(id: number): Promise<Product> {
  const res = await fetch(`${apiBase()}/api/products/${id}`, {
    headers: { Accept: "application/json" },
    cache: "no-store",
  });
  if (res.status === 404) {
    throw new Error("Product not found");
  }
  if (!res.ok) {
    throw new Error(`Could not load product (HTTP ${res.status})`);
  }
  return res.json() as Promise<Product>;
}

export async function fetchCart(token: string): Promise<CartResponse> {
  const res = await fetch(`${apiBase()}/api/cart`, {
    headers: {
      Accept: "application/json",
      ...bearerHeaders(),
      "X-Cart-Token": token,
    },
    cache: "no-store",
  });
  if (res.status === 404 || res.status === 401) {
    await createCartSession();
    return fetchCart(getStoredCartToken()!);
  }
  if (!res.ok) {
    throw new Error(`Could not load cart (HTTP ${res.status})`);
  }
  const data = (await res.json()) as CartResponse;
  return {
    status: data.status ?? "cart",
    items: data.items,
    total: data.total,
  };
}

export async function placeOrder(
  token: string,
  paymentMethod: PaymentMethod,
  shipping: ShippingForm,
  saveToProfile: boolean,
): Promise<CheckoutResult> {
  const res = await fetch(`${apiBase()}/api/checkout`, {
    method: "POST",
    headers: {
      Accept: "application/json",
      "Content-Type": "application/json",
      ...bearerHeaders(),
      "X-Cart-Token": token,
    },
    body: JSON.stringify({
      payment_method: paymentMethod,
      shipping_recipient_name: shipping.recipient_name,
      shipping_phone: shipping.phone,
      shipping_line1: shipping.line1,
      shipping_line2: shipping.line2 || null,
      shipping_city: shipping.city,
      shipping_state: shipping.state,
      shipping_postcode: shipping.postcode,
      shipping_country: shipping.country,
      save_to_profile: saveToProfile,
    }),
  });
  if (!res.ok) {
    let msg = `Checkout failed (HTTP ${res.status})`;
    try {
      const body = (await res.json()) as {
        message?: string;
        errors?: Record<string, string[]>;
      };
      if (body.errors?.order?.[0]) msg = body.errors.order[0];
      else if (body.message) msg = body.message;
    } catch {
      /* ignore */
    }
    throw new Error(msg);
  }
  return res.json() as Promise<CheckoutResult>;
}

export async function addCartItem(
  token: string,
  productId: number,
  quantity: number,
): Promise<void> {
  const res = await fetch(`${apiBase()}/api/cart/items`, {
    method: "POST",
    headers: {
      Accept: "application/json",
      "Content-Type": "application/json",
      ...bearerHeaders(),
      "X-Cart-Token": token,
    },
    body: JSON.stringify({ product_id: productId, quantity }),
  });
  if (!res.ok) {
    let msg = `Could not add item (HTTP ${res.status})`;
    try {
      const body = (await res.json()) as { message?: string; errors?: Record<string, string[]> };
      if (body.errors?.quantity?.[0]) msg = body.errors.quantity[0];
      else if (body.message) msg = body.message;
    } catch {
      /* ignore */
    }
    throw new Error(msg);
  }
}

export async function updateCartItem(
  token: string,
  cartItemId: number,
  quantity: number,
): Promise<void> {
  const res = await fetch(`${apiBase()}/api/cart/items/${cartItemId}`, {
    method: "PATCH",
    headers: {
      Accept: "application/json",
      "Content-Type": "application/json",
      ...bearerHeaders(),
      "X-Cart-Token": token,
    },
    body: JSON.stringify({ quantity }),
  });
  if (!res.ok) {
    let msg = `Could not update quantity (HTTP ${res.status})`;
    try {
      const body = (await res.json()) as { errors?: Record<string, string[]> };
      if (body.errors?.quantity?.[0]) msg = body.errors.quantity[0];
    } catch {
      /* ignore */
    }
    throw new Error(msg);
  }
}

export async function deleteCartItem(token: string, cartItemId: number): Promise<void> {
  const res = await fetch(`${apiBase()}/api/cart/items/${cartItemId}`, {
    method: "DELETE",
    headers: {
      Accept: "application/json",
      ...bearerHeaders(),
      "X-Cart-Token": token,
    },
  });
  if (!res.ok && res.status !== 204) {
    throw new Error(`Could not remove item (HTTP ${res.status})`);
  }
}
