"use client";

import Image from "next/image";
import Link from "next/link";
import { useCallback, useEffect, useState } from "react";
import { CartPanel } from "@/components/CartPanel";
import { ShopHeader } from "@/components/ShopHeader";
import { useCart } from "@/hooks/useCart";
import { fetchProducts } from "@/lib/api";
import { money, parsePrice } from "@/lib/money";
import type { Product } from "@/lib/types";

export default function Home() {
  const [products, setProducts] = useState<Product[]>([]);
  const [catalogLoading, setCatalogLoading] = useState(true);
  const [catalogError, setCatalogError] = useState<string | null>(null);
  const [qtyByProduct, setQtyByProduct] = useState<Record<number, number>>({});
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

  const loadCatalog = useCallback(async () => {
    setCatalogError(null);
    setCatalogLoading(true);
    try {
      const plist = await fetchProducts();
      setProducts(plist);
      const initialQty: Record<number, number> = {};
      for (const p of plist) initialQty[p.id] = 1;
      setQtyByProduct(initialQty);
    } catch (e) {
      setCatalogError(e instanceof Error ? e.message : "Failed to load");
    } finally {
      setCatalogLoading(false);
    }
  }, []);

  useEffect(() => {
    void loadCatalog();
  }, [loadCatalog]);

  return (
    <div className="min-h-full">
      <ShopHeader tagline="Single-page shop · Next.js ↔ Laravel" />

      {catalogError ? (
        <div className="mx-auto max-w-6xl px-4 py-4 sm:px-6">
          <p className="rounded-lg bg-red-50 px-3 py-2 text-sm text-red-800" role="alert">
            {catalogError}
          </p>
        </div>
      ) : null}

      <main className="mx-auto grid max-w-6xl gap-8 px-4 py-8 lg:grid-cols-[1fr_380px] sm:px-6">
        <section aria-labelledby="products-heading">
          <h2 id="products-heading" className="sr-only">
            Product catalogue
          </h2>
          {catalogLoading ? (
            <div className="grid gap-4 sm:grid-cols-2">
              {[1, 2, 3, 4].map((k) => (
                <div
                  key={k}
                  className="h-72 animate-pulse rounded-2xl bg-stone-200/60"
                />
              ))}
            </div>
          ) : (
            <ul className="grid gap-5 sm:grid-cols-2">
              {products.map((p) => (
                <li
                  key={p.id}
                  className="flex flex-col overflow-hidden rounded-2xl border border-stone-200 bg-white shadow-sm transition hover:-translate-y-0.5 hover:shadow-md"
                >
                  <Link
                    href={`/products/${p.id}`}
                    className="relative aspect-[4/3] bg-stone-100 outline-offset-4 focus-visible:ring-2 focus-visible:ring-amber-800"
                  >
                    {p.image_url ? (
                      <Image
                        src={p.image_url}
                        alt={p.name}
                        fill
                        className="object-cover"
                        sizes="(min-width: 1024px) 320px, 50vw"
                      />
                    ) : (
                      <div className="flex h-full items-center justify-center text-stone-400">
                        No image
                      </div>
                    )}
                  </Link>
                  <div className="flex flex-1 flex-col gap-3 p-4">
                    <div>
                      <h3 className="font-display text-lg font-semibold text-stone-900">
                        <Link
                          href={`/products/${p.id}`}
                          className="hover:text-amber-900 hover:underline"
                        >
                          {p.name}
                        </Link>
                      </h3>
                      {p.description ? (
                        <p className="mt-1 line-clamp-2 text-sm text-stone-600">
                          {p.description}
                        </p>
                      ) : null}
                    </div>
                    <div className="mt-auto flex flex-wrap items-end justify-between gap-3">
                      <p className="text-lg font-semibold tabular-nums text-amber-900">
                        {money.format(parsePrice(p))}
                      </p>
                      <p className="text-xs text-stone-500">{p.stock} in stock</p>
                    </div>
                    <div className="flex items-center gap-2">
                      <label className="sr-only" htmlFor={`qty-${p.id}`}>
                        Quantity for {p.name}
                      </label>
                      <input
                        id={`qty-${p.id}`}
                        type="number"
                        min={1}
                        max={p.stock}
                        value={qtyByProduct[p.id] ?? 1}
                        onChange={(ev) => {
                          const v = Number(ev.target.value);
                          setQtyByProduct((prev) => ({
                            ...prev,
                            [p.id]: Number.isFinite(v) && v >= 1 ? v : 1,
                          }));
                        }}
                        className="w-20 rounded-lg border border-stone-200 bg-stone-50 px-2 py-2 text-sm tabular-nums"
                      />
                      <button
                        type="button"
                        disabled={p.stock < 1 || busyId === p.id}
                        onClick={() =>
                          void handleAdd(p.id, qtyByProduct[p.id] ?? 1)
                        }
                        className="flex-1 rounded-lg bg-amber-800 px-3 py-2 text-sm font-medium text-white shadow-sm transition hover:bg-amber-900 disabled:cursor-not-allowed disabled:opacity-50"
                      >
                        {busyId === p.id ? "Adding…" : "Add to cart"}
                      </button>
                    </div>
                  </div>
                </li>
              ))}
            </ul>
          )}
        </section>

        <CartPanel
          lines={cartLines}
          total={cartTotal}
          loading={cartLoading}
          error={cartError}
          busyId={busyId}
          cartToken={cartToken}
          cartStatus={cartStatus}
          onStartNewCart={() => void startNewCart()}
          emptyHint="Your cart is empty. Add products from the catalogue or open a product page."
          onQtyChange={(line, q) => void handleQtyChange(line, q)}
          onRemove={(id) => void handleRemove(id)}
        />
      </main>
    </div>
  );
}
