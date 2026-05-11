"use client";

import Image from "next/image";
import Link from "next/link";
import { useParams } from "next/navigation";
import { useCallback, useEffect, useMemo, useState } from "react";
import { CartPanel } from "@/components/CartPanel";
import { ShopHeader } from "@/components/ShopHeader";
import { useAuth } from "@/contexts/auth-context";
import { useLocale } from "@/contexts/locale-context";
import { useCart } from "@/hooks/useCart";
import {
  fetchProduct,
  fetchProductReviews,
  submitProductReview,
} from "@/lib/api";
import { money, parsePrice } from "@/lib/money";
import type { Product, ProductReview } from "@/lib/types";

function StarRow({
  rating,
  size = "h-5 w-5",
}: {
  rating: number;
  size?: string;
}) {
  return (
    <div className="flex items-center">
      {[1, 2, 3, 4, 5].map((star) => (
        <svg
          key={star}
          className={`${size} ${
            star <= Math.round(rating) ? "text-yellow-400" : "text-stone-200"
          }`}
          fill="currentColor"
          viewBox="0 0 20 20"
          aria-hidden
        >
          <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
        </svg>
      ))}
    </div>
  );
}

export default function ProductPage() {
  const { t, tf } = useLocale();
  const { user } = useAuth();
  const params = useParams();
  const rawId = params.id;
  const id = typeof rawId === "string" ? Number.parseInt(rawId, 10) : NaN;

  const [product, setProduct] = useState<Product | null>(null);
  const [pageLoading, setPageLoading] = useState(true);
  const [notFound, setNotFound] = useState(false);
  const [pageError, setPageError] = useState<string | null>(null);
  const [qty, setQty] = useState(1);

  const [reviews, setReviews] = useState<ProductReview[]>([]);
  const [reviewsLoading, setReviewsLoading] = useState(true);
  const [reviewsError, setReviewsError] = useState<string | null>(null);

  const [myRating, setMyRating] = useState<number>(5);
  const [myComment, setMyComment] = useState("");
  const [submitting, setSubmitting] = useState(false);
  const [submitError, setSubmitError] = useState<string | null>(null);
  const [submitOk, setSubmitOk] = useState(false);

  const {
    cartLines,
    cartTotal,
    cartLoading,
    error: cartError,
    busyId,
    handleAdd,
    handleQtyChange,
    handleRemove,
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
            e instanceof Error ? e.message : t("common.failedLoadProduct"),
          );
        }
      } finally {
        setPageLoading(false);
      }
    },
    [t],
  );

  const loadReviews = useCallback(async (productId: number) => {
    setReviewsLoading(true);
    setReviewsError(null);
    try {
      const data = await fetchProductReviews(productId);
      setReviews(data.data);
      setProduct((prev) =>
        prev
          ? {
              ...prev,
              average_rating: data.average_rating,
              review_count: data.review_count,
            }
          : prev,
      );
    } catch (e) {
      setReviewsError(e instanceof Error ? e.message : "Failed to load reviews");
    } finally {
      setReviewsLoading(false);
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
    void loadReviews(id);
  }, [id, load, loadReviews]);

  const myExistingReview = useMemo(() => {
    if (!user) return null;
    return reviews.find((r) => r.user?.id === user.id) ?? null;
  }, [reviews, user]);

  useEffect(() => {
    if (myExistingReview) {
      setMyRating(myExistingReview.rating);
      setMyComment(myExistingReview.comment ?? "");
    }
  }, [myExistingReview]);

  async function onSubmitReview(e: React.FormEvent): Promise<void> {
    e.preventDefault();
    if (!product || !user) return;
    setSubmitError(null);
    setSubmitOk(false);
    setSubmitting(true);
    try {
      const res = await submitProductReview(
        product.id,
        myRating,
        myComment.trim() || null,
      );
      setSubmitOk(true);
      setProduct((prev) =>
        prev
          ? {
              ...prev,
              average_rating: res.average_rating,
              review_count: res.review_count,
            }
          : prev,
      );
      setReviews((prev) => {
        const withoutMine = prev.filter((r) => r.id !== res.review.id);
        return [res.review, ...withoutMine];
      });
    } catch (err) {
      setSubmitError(
        err instanceof Error ? err.message : "Failed to submit review",
      );
    } finally {
      setSubmitting(false);
    }
  }

  const invalidId = !Number.isFinite(id) || id < 1;
  const avg = product?.average_rating ?? null;
  const reviewCount = product?.review_count ?? 0;

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
            <div className="space-y-10">
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

                  <div className="mt-3 flex items-center gap-2 text-sm text-stone-600">
                    <StarRow rating={avg ?? 0} size="h-5 w-5" />
                    <span className="font-medium text-stone-800">
                      {avg !== null ? avg.toFixed(1) : "—"}
                    </span>
                    <span className="text-stone-500">
                      {tf("product.reviewCount", { count: reviewCount })}
                    </span>
                  </div>

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
              </div>

              <section className="rounded-2xl border border-stone-200 bg-white p-5 shadow-sm">
                <div className="flex flex-wrap items-baseline justify-between gap-2">
                  <h2 className="font-display text-xl font-semibold text-stone-900">
                    {t("product.reviewsTitle")}
                  </h2>
                  <p className="text-sm text-stone-600">
                    {avg !== null
                      ? tf("product.averageOfCount", {
                          average: avg.toFixed(1),
                          count: reviewCount,
                        })
                      : t("product.noReviewsYet")}
                  </p>
                </div>

                {user ? (
                  <form
                    onSubmit={(e) => void onSubmitReview(e)}
                    className="mt-6 rounded-xl border border-stone-200 bg-stone-50/60 p-4"
                  >
                    <p className="text-sm font-medium text-stone-800">
                      {myExistingReview
                        ? t("product.updateYourReview")
                        : t("product.leaveAReview")}
                    </p>
                    <div className="mt-3 flex items-center gap-2">
                      {[1, 2, 3, 4, 5].map((star) => (
                        <button
                          key={star}
                          type="button"
                          onClick={() => setMyRating(star)}
                          aria-label={tf("product.starsAria", { count: star })}
                          className="text-2xl leading-none"
                        >
                          <span
                            className={
                              star <= myRating
                                ? "text-yellow-500"
                                : "text-stone-300"
                            }
                          >
                            ★
                          </span>
                        </button>
                      ))}
                      <span className="ml-2 text-sm tabular-nums text-stone-600">
                        {myRating}/5
                      </span>
                    </div>
                    <label
                      htmlFor="review-comment"
                      className="mt-4 block text-sm font-medium text-stone-700"
                    >
                      {t("product.commentLabel")}
                    </label>
                    <textarea
                      id="review-comment"
                      value={myComment}
                      onChange={(e) => setMyComment(e.target.value)}
                      rows={3}
                      maxLength={2000}
                      placeholder={t("product.commentPlaceholder")}
                      className="mt-1 w-full rounded-lg border border-stone-200 px-3 py-2 text-sm"
                    />
                    {submitError ? (
                      <p
                        className="mt-2 text-sm text-red-800"
                        role="alert"
                      >
                        {submitError}
                      </p>
                    ) : null}
                    {submitOk ? (
                      <p className="mt-2 text-sm text-emerald-800">
                        {t("product.reviewSaved")}
                      </p>
                    ) : null}
                    <button
                      type="submit"
                      disabled={submitting}
                      className="mt-4 rounded-lg bg-amber-800 px-4 py-2 text-sm font-medium text-white hover:bg-amber-900 disabled:cursor-not-allowed disabled:opacity-50"
                    >
                      {submitting
                        ? t("product.submittingReview")
                        : myExistingReview
                          ? t("product.updateReview")
                          : t("product.submitReview")}
                    </button>
                  </form>
                ) : (
                  <p className="mt-6 rounded-xl border border-dashed border-stone-300 bg-stone-50/80 px-3 py-3 text-sm text-stone-700">
                    {t("product.loginToReviewPrompt")}{" "}
                    <Link
                      href={`/login?redirect=${encodeURIComponent(
                        `/products/${product.id}`,
                      )}`}
                      className="font-medium text-amber-900 hover:underline"
                    >
                      {t("nav.logIn")}
                    </Link>
                  </p>
                )}

                <div className="mt-6 space-y-4">
                  {reviewsLoading ? (
                    <p className="text-sm text-stone-500">
                      {t("common.loading")}
                    </p>
                  ) : reviewsError ? (
                    <p className="text-sm text-red-800" role="alert">
                      {reviewsError}
                    </p>
                  ) : reviews.length === 0 ? (
                    <p className="text-sm text-stone-500">
                      {t("product.noReviewsYet")}
                    </p>
                  ) : (
                    reviews.map((r) => (
                      <article
                        key={r.id}
                        className="rounded-xl border border-stone-200 p-4"
                      >
                        <header className="flex items-center justify-between gap-3">
                          <div className="flex items-center gap-3">
                            <div className="flex h-9 w-9 items-center justify-center overflow-hidden rounded-full bg-gradient-to-br from-amber-100 to-amber-200 text-sm font-semibold text-amber-900">
                              {r.user?.avatar_url ? (
                                <Image
                                  src={r.user.avatar_url}
                                  alt=""
                                  width={36}
                                  height={36}
                                  className="h-full w-full object-cover"
                                  unoptimized
                                />
                              ) : (
                                (r.user?.name ?? "?").slice(0, 1).toUpperCase()
                              )}
                            </div>
                            <div>
                              <p className="text-sm font-medium text-stone-900">
                                {r.user?.name ?? t("product.anonymousReviewer")}
                              </p>
                              <p className="text-xs text-stone-500">
                                {r.created_at
                                  ? new Date(r.created_at).toLocaleDateString()
                                  : ""}
                              </p>
                            </div>
                          </div>
                          <StarRow rating={r.rating} size="h-4 w-4" />
                        </header>
                        {r.comment ? (
                          <p className="mt-3 whitespace-pre-line text-sm text-stone-700">
                            {r.comment}
                          </p>
                        ) : null}
                      </article>
                    ))
                  )}
                </div>
              </section>
            </div>
          ) : null}
        </div>

        <CartPanel
          lines={cartLines}
          total={cartTotal}
          loading={cartLoading}
          error={cartError}
          busyId={busyId}
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
