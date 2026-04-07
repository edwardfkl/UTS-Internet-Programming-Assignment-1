"use client";

import Image from "next/image";
import Link from "next/link";
import { useParams } from "next/navigation";
import { useCallback, useEffect, useState } from "react";
import { CartPanel } from "@/components/CartPanel";
import { ShopHeader } from "@/components/ShopHeader";
import { useCart } from "@/hooks/useCart";
import { fetchProduct } from "@/lib/api";
import { money, parsePrice } from "@/lib/money";
import type { Product } from "@/lib/types";

export default function ProductPage() {
  const params = useParams();
  const rawId = params.id;
  const id = typeof rawId === "string" ? Number.parseInt(rawId, 10) : NaN;

  const [product, setProduct] = useState<Product | null>(null);
  const [pageLoading, setPageLoading] = useState(true);
  const [notFound, setNotFound] = useState(false);
  const [pageError, setPageError] = useState<string | null>(null);
  const [qty, setQty] = useState(1);

  const {
    cartLines,
    cartTotal,
    cartLoading,
    error: cartError,
    busyId,
    handleAdd,
    handleQtyChange,
    handleRemove,
    cartToken,
    cartStatus,
    startNewCart,
  } = useCart();

  const load = useCallback(async (productId: number) => {
    setPageLoading(true);
    setNotFound(false);
    setPageError(null);
    try {
      const p = await fetchProduct(productId);
      setProduct(p);
      setQty(1);
    } catch (e) {
      setProduct(null);
      if (e instanceof Error && e.message === "Product not found") {
        setNotFound(true);
      } else {
        setPageError(e instanceof Error ? e.message : "Failed to load product");
      }
    } finally {
      setPageLoading(false);
    }
  }, []);

  useEffect(() => {
    if (!Number.isFinite(id) || id < 1) {
      setNotFound(true);
      setPageLoading(false);
      setProduct(null);
      return;
    }
    void load(id);
  }, [id, load]);

  const invalidId = !Number.isFinite(id) || id < 1;

  return (
    <div className="min-h-full">
      <ShopHeader />

      <main className="mx-auto grid max-w-6xl gap-8 px-4 py-8 lg:grid-cols-[1fr_380px] sm:px-6">
        <div>
          <nav className="mb-6 text-sm text-stone-600">
            <Link href="/" className="font-medium text-amber-900 hover:underline">
              Catalog
            </Link>
            <span className="mx-2 text-stone-400" aria-hidden>
              /
            </span>
            {product ? (
              <span className="text-stone-800">{product.name}</span>
            ) : pageLoading ? (
              <span>…</span>
            ) : (
              <span>Product</span>
            )}
          </nav>

          {pageLoading ? (
            <div className="grid gap-8 lg:grid-cols-2">
              <div className="aspect-square animate-pulse rounded-2xl bg-stone-200/70" />
              <div className="space-y-4">
                <div className="h-10 w-3/4 animate-pulse rounded bg-stone-200/70" />
                <div className="h-24 animate-pulse rounded bg-stone-200/70" />
              </div>
            </div>
          ) : pageError ? (
            <div className="rounded-2xl border border-stone-200 bg-white p-8 shadow-sm">
              <p className="text-sm text-red-800" role="alert">
                {pageError}
              </p>
              <Link
                href="/"
                className="mt-4 inline-block text-sm font-medium text-amber-900 hover:underline"
              >
                Back to catalogue
              </Link>
            </div>
          ) : notFound || invalidId ? (
            <div className="rounded-2xl border border-stone-200 bg-white p-8 text-center shadow-sm">
              <h1 className="font-display text-xl font-semibold text-stone-900">
                Product not found
              </h1>
              <p className="mt-2 text-sm text-stone-600">
                This product may have been removed or the link is invalid.
              </p>
              <Link
                href="/"
                className="mt-6 inline-block rounded-lg bg-amber-800 px-4 py-2 text-sm font-medium text-white hover:bg-amber-900"
              >
                Back to catalogue
              </Link>
            </div>
          ) : product ? (
            <div className="grid gap-8 lg:grid-cols-2 lg:gap-10">
              <div className="relative aspect-square overflow-hidden rounded-2xl border border-stone-200 bg-stone-100">
                {product.image_url ? (
                  <Image
                    src={product.image_url}
                    alt={product.name}
                    fill
                    className="object-cover"
                    sizes="(min-width: 1024px) 480px, 100vw"
                    priority
                  />
                ) : (
                  <div className="flex h-full items-center justify-center text-stone-400">
                    No image
                  </div>
                )}
              </div>
              <div className="flex flex-col">
                <h1 className="font-display text-3xl font-semibold text-stone-900 sm:text-4xl">
                  {product.name}
                </h1>
                <p className="mt-4 text-2xl font-semibold tabular-nums text-amber-900">
                  {money.format(parsePrice(product))}
                </p>
                <p className="mt-2 text-sm text-stone-600">
                  {product.stock} in stock
                </p>
                {product.description ? (
                  <p className="mt-6 text-base leading-relaxed text-stone-700">
                    {product.description}
                  </p>
                ) : null}
                <div className="mt-8 flex flex-wrap items-center gap-3">
                  <label className="sr-only" htmlFor="detail-qty">
                    Quantity
                  </label>
                  <input
                    id="detail-qty"
                    type="number"
                    min={1}
                    max={product.stock}
                    value={qty}
                    onChange={(ev) => {
                      const v = Number(ev.target.value);
                      setQty(Number.isFinite(v) && v >= 1 ? v : 1);
                    }}
                    className="w-24 rounded-lg border border-stone-200 bg-stone-50 px-3 py-2 text-sm tabular-nums"
                  />
                  <button
                    type="button"
                    disabled={product.stock < 1 || busyId === product.id}
                    onClick={() => void handleAdd(product.id, qty)}
                    className="rounded-lg bg-amber-800 px-5 py-2.5 text-sm font-medium text-white shadow-sm transition hover:bg-amber-900 disabled:cursor-not-allowed disabled:opacity-50"
                  >
                    {busyId === product.id ? "Adding…" : "Add to cart"}
                  </button>
                </div>
              </div>
            </div>
          ) : null}
        </div>

        <CartPanel
          lines={cartLines}
          total={cartTotal}
          loading={cartLoading}
          error={cartError}
          busyId={busyId}
          cartToken={cartToken}
          cartStatus={cartStatus}
          onStartNewCart={() => void startNewCart()}
          emptyHint="Your cart is empty. Add this product or browse the catalogue."
          onQtyChange={(line, q) => void handleQtyChange(line, q)}
          onRemove={(lineId) => void handleRemove(lineId)}
        />
      </main>
    </div>
  );
}
