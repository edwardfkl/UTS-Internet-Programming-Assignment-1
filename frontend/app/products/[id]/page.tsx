"use client";

import Image from "next/image";
import Link from "next/link";
import { useParams } from "next/navigation";
import { useCallback, useEffect, useState } from "react";
import { CartPanel } from "@/components/CartPanel";
import { ShopHeader } from "@/components/ShopHeader";
import { useLocale } from "@/contexts/locale-context";
import { useCart } from "@/hooks/useCart";
import { fetchProduct } from "@/lib/api";
import { money, parsePrice } from "@/lib/money";
import type { Product } from "@/lib/types";

export default function ProductPage() {
  const { t, tf } = useLocale();
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

  const load = useCallback(
    async (productId: number) => {
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
          setPageError(
            e instanceof Error ? e.message : t("common.failedLoadProduct")
          );
        }
      } finally {
        setPageLoading(false);
      }
    },
    [t]
  );

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
            <Link
              href="/"
              className="font-medium text-amber-900 hover:underline"
            >
              {t("nav.catalog")}
            </Link>
            <span className="mx-2 text-stone-400" aria-hidden>
              /
            </span>
            {product ? (
              <span className="text-stone-800">{product.name}</span>
            ) : pageLoading ? (
              <span>…</span>
            ) : (
              <span>{t("product.breadcrumbProduct")}</span>
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
                {t("product.backToCatalogue")}
              </Link>
            </div>
          ) : notFound || invalidId ? (
            <div className="rounded-2xl border border-stone-200 bg-white p-8 text-center shadow-sm">
              <h1 className="font-display text-xl font-semibold text-stone-900">
                {t("common.productNotFound")}
              </h1>
              <p className="mt-2 text-sm text-stone-600">
                {t("product.notFoundHint")}
              </p>
              <Link
                href="/"
                className="mt-6 inline-block rounded-lg bg-amber-800 px-4 py-2 text-sm font-medium text-white hover:bg-amber-900"
              >
                {t("product.backToCatalogue")}
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
                    {t("common.noImage")}
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
                  {tf("common.inStock", { count: product.stock })}
                </p>
                {product.description ? (
                  <p className="mt-6 text-base leading-relaxed text-stone-700">
                    {product.description}
                  </p>
                ) : null}
                <div className="mt-8 flex flex-wrap items-center gap-3">
                  <label className="sr-only" htmlFor="detail-qty">
                    {t("product.detailQtySr")}
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
                    {busyId === product.id
                      ? t("home.adding")
                      : t("home.addToCart")}
                  </button>
                </div>
              </div>
              <div className="flex flex-col items-center py-4 w-fit">
                <div className="text-6xl font-semibold text-gray-900 tracking-tighter">
                  {product.average_rating}
                </div>

                <div className="flex items-center mt-2 mb-1">
                  {[1, 2, 3, 4, 5].map((star) => (
                    <svg
                      key={star}
                      className={`w-6 h-6 ${
                        star <= Math.round(product.average_rating || 0)
                          ? "text-yellow-400"
                          : "text-gray-200"
                      }`}
                      fill="currentColor"
                      viewBox="0 0 20 20"
                    >
                      <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                    </svg>
                  ))}
                </div>

                <div className="text-l text-gray-600">
                  {tf("product.reviewCount", {
                    count: product.review_count || 0,
                  })}
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
          emptyHintKey="product"
          onQtyChange={(line, q) => void handleQtyChange(line, q)}
          onRemove={(lineId) => void handleRemove(lineId)}
        />
      </main>
    </div>
  );
}
