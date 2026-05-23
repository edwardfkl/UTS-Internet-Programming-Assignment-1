import type {
  CartResponse,
  CheckoutResult,
  PaymentMethod,
  Product,
  ProductReview,
  ProductReviewsResponse,
  PromoPreview,
  ShippingForm,
  UserOrderDetail,
  UserOrderSummary,
} from "./types";
import { getAuthToken } from "./authToken";

function bearerHeaders(): Record<string, string> {
  const t = getAuthToken();
  return t ? { Authorization: `Bearer ${t}` } : {};
}

const CART_STORAGE_KEY = "studio_supply_cart_token";

export function apiBase(): string {
  return process.env.NEXT_PUBLIC_API_URL ?? "http://127.0.0.1:8000";
}

/** Laravel Blade admin (session auth); opens same API origin as `/admin`. */
export function adminPanelUrl(): string {
  return `${apiBase().replace(/\/$/, "")}/admin`;
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
  const existing = getStoredCartToken();
  if (existing) return existing;
  return createCartSession();
}

function isStaleCartResponse(status: number): boolean {
  return status === 404 || status === 401 || status === 403;
}

function cartRequestHeaders(token: string): Record<string, string> {
  return {
    Accept: "application/json",
    ...bearerHeaders(),
    "X-Cart-Token": token,
  };
}

/** Cart API call; replaces invalid/deleted cart token once and retries. */
async function fetchCartApi(
  token: string,
  path: string,
  init: RequestInit = {},
): Promise<Response> {
  const url = `${apiBase().replace(/\/$/, "")}${path}`;
  const request = (cartToken: string) =>
    fetch(url, {
      ...init,
      headers: {
        ...cartRequestHeaders(cartToken),
        ...(init.headers as Record<string, string> | undefined),
      },
    });

  let res = await request(token);
  if (isStaleCartResponse(res.status)) {
    await createCartSession();
    res = await request(getStoredCartToken()!);
  }
  return res;
}

export async function fetchProducts(search?: string): Promise<Product[]> {
  const q = search?.trim();
  const url = new URL(`${apiBase().replace(/\/$/, "")}/api/products`);
  if (q) url.searchParams.set("q", q);
  const res = await fetch(url.toString(), {
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
  const res = await fetchCartApi(token, "/api/cart", { cache: "no-store" });
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
  promoCode?: string | null,
): Promise<CheckoutResult> {
  const res = await fetchCartApi(token, "/api/checkout", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({
      payment_method: paymentMethod,
      promo_code: promoCode?.trim() ? promoCode.trim() : null,
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
      if (body.errors?.promo_code?.[0]) msg = body.errors.promo_code[0];
      else if (body.errors?.order?.[0]) msg = body.errors.order[0];
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
  const res = await fetchCartApi(token, "/api/cart/items", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
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
  const res = await fetchCartApi(token, `/api/cart/items/${cartItemId}`, {
    method: "PATCH",
    headers: { "Content-Type": "application/json" },
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
  const res = await fetchCartApi(token, `/api/cart/items/${cartItemId}`, {
    method: "DELETE",
  });
  if (!res.ok && res.status !== 204) {
    throw new Error(`Could not remove item (HTTP ${res.status})`);
  }
}

export async function fetchUserOrders(): Promise<UserOrderSummary[]> {
  const res = await fetch(`${apiBase()}/api/orders`, {
    headers: { Accept: "application/json", ...bearerHeaders() },
    cache: "no-store",
  });
  if (!res.ok) {
    throw new Error(`Could not load orders (HTTP ${res.status})`);
  }
  const body = (await res.json()) as { data: UserOrderSummary[] };
  return body.data;
}

export async function fetchUserOrder(id: number): Promise<UserOrderDetail> {
  const res = await fetch(`${apiBase()}/api/orders/${id}`, {
    headers: { Accept: "application/json", ...bearerHeaders() },
    cache: "no-store",
  });
  if (!res.ok) {
    throw new Error(`Could not load order (HTTP ${res.status})`);
  }
  return res.json() as Promise<UserOrderDetail>;
}

export async function fetchProductReviews(
  productId: number,
): Promise<ProductReviewsResponse> {
  const res = await fetch(`${apiBase()}/api/products/${productId}/reviews`, {
    headers: { Accept: "application/json" },
    cache: "no-store",
  });
  if (!res.ok) {
    throw new Error(`Could not load reviews (HTTP ${res.status})`);
  }
  return res.json() as Promise<ProductReviewsResponse>;
}

export async function submitProductReview(
  productId: number,
  rating: number,
  comment: string | null,
): Promise<{
  review: ProductReview;
  average_rating: number | null;
  review_count: number;
}> {
  const res = await fetch(`${apiBase()}/api/products/${productId}/reviews`, {
    method: "POST",
    headers: {
      Accept: "application/json",
      "Content-Type": "application/json",
      ...bearerHeaders(),
    },
    body: JSON.stringify({ rating, comment: comment || null }),
  });
  if (!res.ok) {
    let msg = `Could not submit review (HTTP ${res.status})`;
    try {
      const body = (await res.json()) as {
        message?: string;
        errors?: Record<string, string[]>;
      };
      const first = body.errors && Object.values(body.errors)[0]?.[0];
      if (first) msg = first;
      else if (body.message) msg = body.message;
    } catch {
      /* ignore */
    }
    throw new Error(msg);
  }
  return res.json() as Promise<{
    review: ProductReview;
    average_rating: number | null;
    review_count: number;
  }>;
}

export async function previewPromoCode(
  code: string,
  subtotal: number,
): Promise<PromoPreview> {
  const res = await fetch(`${apiBase()}/api/promo-codes/preview`, {
    method: "POST",
    headers: {
      Accept: "application/json",
      "Content-Type": "application/json",
      ...bearerHeaders(),
    },
    body: JSON.stringify({ code, subtotal }),
  });
  const data = (await res.json().catch(() => null)) as PromoPreview | null;
  if (!data) {
    throw new Error(`Could not validate promo code (HTTP ${res.status})`);
  }
  return data;
}