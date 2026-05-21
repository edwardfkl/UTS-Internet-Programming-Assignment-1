"use client";

import { useCallback, useEffect, useRef, useState } from "react";
import {
  addCartItem,
  deleteCartItem,
  ensureCartToken,
  fetchCart,
  resetCartSession,
  updateCartItem,
} from "@/lib/api";
import { parsePrice } from "@/lib/money";
import type { CartLine } from "@/lib/types";

const QTY_SYNC_DEBOUNCE_MS = 280;

export function useCart() {
  const [cartLines, setCartLines] = useState<CartLine[]>([]);
  const [cartTotal, setCartTotal] = useState(0);
  const [cartLoading, setCartLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [busyId, setBusyId] = useState<number | null>(null);
  const [cartToken, setCartToken] = useState<string | null>(null);
  const [cartStatus, setCartStatus] = useState<string>("cart");
  const pendingSyncRef = useRef<Map<number, ReturnType<typeof setTimeout>>>(
    new Map(),
  );

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

  useEffect(() => {
    const pending = pendingSyncRef.current;
    return () => {
      pending.forEach((handle) => clearTimeout(handle));
      pending.clear();
    };
  }, []);

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
    (line: CartLine, nextQty: number) => {
      const clamped = Math.max(1, Math.min(nextQty, line.product.stock));
      setError(null);

      const unit = parsePrice(line.product);
      setCartLines((ls) =>
        ls.map((l) =>
          l.id === line.id
            ? { ...l, quantity: clamped, line_total: unit * clamped }
            : l,
        ),
      );
      setCartTotal((t) => t - line.line_total + unit * clamped);

      const existing = pendingSyncRef.current.get(line.id);
      if (existing) clearTimeout(existing);

      const handle = setTimeout(async () => {
        pendingSyncRef.current.delete(line.id);
        try {
          const token = await ensureCartToken();
          await updateCartItem(token, line.id, clamped);
          await refreshCart();
        } catch (e) {
          setError(e instanceof Error ? e.message : "Update failed");
          try {
            await refreshCart();
          } catch {
            /* keep showing the original error */
          }
        }
      }, QTY_SYNC_DEBOUNCE_MS);
      pendingSyncRef.current.set(line.id, handle);
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
