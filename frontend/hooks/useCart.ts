"use client";

import { useCallback, useEffect, useState } from "react";
import {
  addCartItem,
  deleteCartItem,
  ensureCartToken,
  fetchCart,
  resetCartSession,
  updateCartItem,
} from "@/lib/api";
import type { CartLine } from "@/lib/types";

export function useCart() {
  const [cartLines, setCartLines] = useState<CartLine[]>([]);
  const [cartTotal, setCartTotal] = useState(0);
  const [cartLoading, setCartLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [busyId, setBusyId] = useState<number | null>(null);
  const [cartToken, setCartToken] = useState<string | null>(null);
  const [cartStatus, setCartStatus] = useState<string>("cart");

  const refreshCart = useCallback(async () => {
    const token = await ensureCartToken();
    const c = await fetchCart(token);
    setCartLines(c.items);
    setCartTotal(c.total);
    setCartStatus(c.status);
    setCartToken(token);
  }, []);

  const startNewCart = useCallback(async () => {
    setError(null);
    const token = await resetCartSession();
    const c = await fetchCart(token);
    setCartLines(c.items);
    setCartTotal(c.total);
    setCartStatus(c.status);
    setCartToken(token);
  }, []);

  useEffect(() => {
    let cancelled = false;
    (async () => {
      setCartLoading(true);
      setError(null);
      try {
        await refreshCart();
      } catch (e) {
        if (!cancelled) {
          setError(e instanceof Error ? e.message : "Failed to load cart");
        }
      } finally {
        if (!cancelled) setCartLoading(false);
      }
    })();
    return () => {
      cancelled = true;
    };
  }, [refreshCart]);

  const handleAdd = useCallback(
    async (productId: number, quantity: number) => {
      setError(null);
      const token = await ensureCartToken();
      setBusyId(productId);
      try {
        await addCartItem(token, productId, quantity);
        await refreshCart();
      } catch (e) {
        setError(e instanceof Error ? e.message : "Could not add to cart");
      } finally {
        setBusyId(null);
      }
    },
    [refreshCart],
  );

  const handleQtyChange = useCallback(
    async (line: CartLine, nextQty: number) => {
      if (nextQty < 1) return;
      setError(null);
      const token = await ensureCartToken();
      setBusyId(line.id);
      try {
        await updateCartItem(token, line.id, nextQty);
        await refreshCart();
      } catch (e) {
        setError(e instanceof Error ? e.message : "Update failed");
      } finally {
        setBusyId(null);
      }
    },
    [refreshCart],
  );

  const handleRemove = useCallback(
    async (lineId: number) => {
      setError(null);
      const token = await ensureCartToken();
      setBusyId(lineId);
      try {
        await deleteCartItem(token, lineId);
        await refreshCart();
      } catch (e) {
        setError(e instanceof Error ? e.message : "Remove failed");
      } finally {
        setBusyId(null);
      }
    },
    [refreshCart],
  );

  return {
    cartLines,
    cartTotal,
    cartLoading,
    cartToken,
    cartStatus,
    error,
    setError,
    busyId,
    refreshCart,
    startNewCart,
    handleAdd,
    handleQtyChange,
    handleRemove,
  };
}
