import { afterEach, beforeEach, describe, expect, it, vi } from "vitest";
import {
  fetchProduct,
  fetchProducts,
  fetchProductReviews,
  fetchUserOrder,
  fetchUserOrders,
  previewPromoCode,
  submitProductReview,
} from "@/lib/api";
import { setAuthToken, clearAuthToken } from "@/lib/authToken";
import {
  installFetchMock,
  jsonResponse,
  TEST_API_BASE,
} from "./helpers/fetchMock";

describe("catalog and orders API client", () => {
  beforeEach(() => {
    window.localStorage.clear();
    vi.stubEnv("NEXT_PUBLIC_API_URL", TEST_API_BASE);
  });

  afterEach(() => {
    vi.unstubAllGlobals();
  });

  it("fetchProducts loads the catalogue", async () => {
    const products = [
      {
        id: 1,
        name: "Pen",
        description: null,
        price: "10.00",
        image_url: null,
        stock: 5,
      },
    ];
    installFetchMock((url) => {
      if (url.includes("/api/products") && !url.includes("/reviews")) {
        return jsonResponse(products);
      }
      return jsonResponse({}, 404);
    });
    await expect(fetchProducts()).resolves.toEqual(products);
  });

  it("fetchProducts appends search query", async () => {
    const fetchMock = installFetchMock((url) => {
      if (url.includes("q=ink")) return jsonResponse([]);
      return jsonResponse({}, 404);
    });
    await fetchProducts("  ink  ");
    expect(String(fetchMock.mock.calls[0][0])).toContain("q=ink");
  });

  it("fetchProduct throws for 404", async () => {
    installFetchMock(() => jsonResponse({}, 404));
    await expect(fetchProduct(99)).rejects.toThrow("Product not found");
  });

  it("fetchProductReviews returns review payload", async () => {
    const payload = {
      data: [],
      average_rating: 4.5,
      review_count: 2,
    };
    installFetchMock((url) => {
      if (url.endsWith("/api/products/1/reviews")) {
        return jsonResponse(payload);
      }
      return jsonResponse({}, 404);
    });
    await expect(fetchProductReviews(1)).resolves.toEqual(payload);
  });

  it("submitProductReview requires bearer token", async () => {
    setAuthToken("jwt-r");
    installFetchMock((url, init) => {
      if (url.endsWith("/api/products/2/reviews") && init?.method === "POST") {
        return jsonResponse({
          review: {
            id: 1,
            rating: 5,
            comment: "Great",
            created_at: null,
            user: null,
          },
          average_rating: 5,
          review_count: 1,
        });
      }
      return jsonResponse({}, 404);
    });
    const result = await submitProductReview(2, 5, "Great");
    expect(result.review_count).toBe(1);
    clearAuthToken();
  });

  it("fetchUserOrders returns order summaries", async () => {
    setAuthToken("jwt-o");
    const orders = [
      {
        id: 10,
        order_reference: "SSP-000010",
        status: "pending_payment",
        total_amount: 50,
        placed_at: "2026-01-01T00:00:00Z",
      },
    ];
    installFetchMock((url) => {
      if (url.endsWith("/api/orders") && !url.includes("/orders/")) {
        return jsonResponse({ data: orders });
      }
      return jsonResponse({}, 404);
    });
    await expect(fetchUserOrders()).resolves.toEqual(orders);
    clearAuthToken();
  });

  it("fetchUserOrder loads a single order", async () => {
    setAuthToken("jwt-o");
    const detail = {
      id: 10,
      order_reference: "SSP-000010",
      status: "paid",
      lines: [],
    };
    installFetchMock((url) => {
      if (url.endsWith("/api/orders/10")) {
        return jsonResponse(detail);
      }
      return jsonResponse({}, 404);
    });
    await expect(fetchUserOrder(10)).resolves.toEqual(detail);
    clearAuthToken();
  });

  it("previewPromoCode returns validation result", async () => {
    setAuthToken("jwt-promo");
    installFetchMock((url, init) => {
      if (url.endsWith("/api/promo-codes/preview") && init?.method === "POST") {
        return jsonResponse({
          valid: true,
          code: "SAVE10",
          discount: 10,
          total: 90,
        });
      }
      return jsonResponse({}, 404);
    });
    const result = await previewPromoCode("save10", 100);
    expect(result.valid).toBe(true);
    expect(result.discount).toBe(10);
    clearAuthToken();
  });
});
