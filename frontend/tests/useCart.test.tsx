import { act, renderHook, waitFor } from "@testing-library/react";
import { afterEach, beforeEach, describe, expect, it, vi } from "vitest";
import { useCart } from "@/hooks/useCart";
import type { CartLine } from "@/lib/types";

const STALE_TOKEN = "stale-token";
const NEW_TOKEN = "new-token";

const sampleLine: CartLine = {
  id: 1,
  quantity: 1,
  line_total: 25,
  product: {
    id: 10,
    name: "Test product",
    description: null,
    price: "25.00",
    image_url: null,
    stock: 5,
  },
};

const emptyCart = { status: "cart", items: [] as CartLine[], total: 0 };
const cartWithLine = { status: "cart", items: [sampleLine], total: 25 };

const apiMocks = vi.hoisted(() => ({
  ensureCartToken: vi.fn(),
  fetchCart: vi.fn(),
  addCartItem: vi.fn(),
  updateCartItem: vi.fn(),
  deleteCartItem: vi.fn(),
  resetCartSession: vi.fn(),
}));

vi.mock("@/lib/api", () => apiMocks);

const {
  ensureCartToken,
  fetchCart,
  addCartItem,
  updateCartItem,
  deleteCartItem,
  resetCartSession,
} = apiMocks;

describe("useCart", () => {
  beforeEach(() => {
    vi.clearAllMocks();
    ensureCartToken.mockResolvedValue(STALE_TOKEN);
    fetchCart.mockResolvedValue(emptyCart);
    addCartItem.mockResolvedValue(undefined);
    updateCartItem.mockResolvedValue(undefined);
    deleteCartItem.mockResolvedValue(undefined);
    resetCartSession.mockResolvedValue(NEW_TOKEN);
  });

  afterEach(() => {
    vi.useRealTimers();
  });

  it("loads the cart on mount", async () => {
    fetchCart.mockResolvedValueOnce(cartWithLine);

    const { result } = renderHook(() => useCart());

    await waitFor(() => {
      expect(result.current.cartLoading).toBe(false);
    });

    expect(ensureCartToken).toHaveBeenCalled();
    expect(fetchCart).toHaveBeenCalledWith(STALE_TOKEN);
    expect(result.current.cartLines).toEqual(cartWithLine.items);
    expect(result.current.cartTotal).toBe(25);
    expect(result.current.cartToken).toBe(STALE_TOKEN);
    expect(result.current.error).toBeNull();
  });

  it("shows an error when the initial cart load fails", async () => {
    fetchCart.mockRejectedValueOnce(new Error("Could not load cart"));

    const { result } = renderHook(() => useCart());

    await waitFor(() => {
      expect(result.current.cartLoading).toBe(false);
    });

    expect(result.current.error).toBe("Could not load cart");
    expect(result.current.cartLines).toEqual([]);
  });

  it("handleAdd refreshes the cart after a successful add", async () => {
    fetchCart
      .mockResolvedValueOnce(emptyCart)
      .mockResolvedValueOnce(cartWithLine);

    const { result } = renderHook(() => useCart());

    await waitFor(() => {
      expect(result.current.cartLoading).toBe(false);
    });

    await act(async () => {
      await result.current.handleAdd(10, 1);
    });

    expect(addCartItem).toHaveBeenCalledWith(STALE_TOKEN, 10, 1);
    expect(fetchCart).toHaveBeenCalledTimes(2);
    expect(result.current.cartLines).toEqual(cartWithLine.items);
    expect(result.current.error).toBeNull();
    expect(result.current.busyId).toBeNull();
  });

  it("handleAdd surfaces API errors (e.g. before stale-token recovery existed)", async () => {
    fetchCart.mockResolvedValueOnce(emptyCart);
    addCartItem.mockRejectedValueOnce(new Error("Order not found"));

    const { result } = renderHook(() => useCart());

    await waitFor(() => {
      expect(result.current.cartLoading).toBe(false);
    });

    await act(async () => {
      await result.current.handleAdd(10, 1);
    });

    expect(result.current.error).toBe("Order not found");
    expect(fetchCart).toHaveBeenCalledTimes(1);
  });

  it("startNewCart clears errors and loads a fresh cart", async () => {
    fetchCart
      .mockResolvedValueOnce(emptyCart)
      .mockResolvedValueOnce(emptyCart);

    const { result } = renderHook(() => useCart());

    await waitFor(() => {
      expect(result.current.cartLoading).toBe(false);
    });

    act(() => {
      result.current.setError("Old error");
    });

    await act(async () => {
      await result.current.startNewCart();
    });

    expect(resetCartSession).toHaveBeenCalled();
    expect(fetchCart).toHaveBeenLastCalledWith(NEW_TOKEN);
    expect(result.current.cartToken).toBe(NEW_TOKEN);
    expect(result.current.error).toBeNull();
  });

  it("handleRemove deletes a line and refreshes", async () => {
    fetchCart
      .mockResolvedValueOnce(cartWithLine)
      .mockResolvedValueOnce(emptyCart);

    const { result } = renderHook(() => useCart());

    await waitFor(() => {
      expect(result.current.cartLoading).toBe(false);
    });

    await act(async () => {
      await result.current.handleRemove(1);
    });

    expect(deleteCartItem).toHaveBeenCalledWith(STALE_TOKEN, 1);
    expect(result.current.cartLines).toEqual([]);
    expect(result.current.error).toBeNull();
  });

  it("handleQtyChange debounces server sync then refreshes", async () => {
    fetchCart
      .mockResolvedValueOnce(cartWithLine)
      .mockResolvedValueOnce({
        ...cartWithLine,
        items: [{ ...sampleLine, quantity: 2, line_total: 50 }],
        total: 50,
      });

    const { result } = renderHook(() => useCart());

    await waitFor(() => {
      expect(result.current.cartLoading).toBe(false);
    });

    act(() => {
      result.current.handleQtyChange(sampleLine, 2);
    });

    expect(result.current.cartLines[0].quantity).toBe(2);
    expect(updateCartItem).not.toHaveBeenCalled();

    await act(async () => {
      await new Promise((resolve) => setTimeout(resolve, 300));
    });

    expect(updateCartItem).toHaveBeenCalledWith(STALE_TOKEN, 1, 2);
    expect(fetchCart).toHaveBeenCalledTimes(2);
    expect(result.current.cartTotal).toBe(50);
  });
});
