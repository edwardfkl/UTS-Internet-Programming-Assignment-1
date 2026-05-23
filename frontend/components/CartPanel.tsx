"use client";

import Image from "next/image";
import Link from "next/link";
import { useAuth } from "@/contexts/auth-context";
import { useCurrency } from "@/contexts/currency-context";
import { useLocale } from "@/contexts/locale-context";
import { parsePrice } from "@/lib/money";
import type { CartLine } from "@/lib/types";

type CartPanelProps = {
  lines: CartLine[];
  total: number;
  loading: boolean;
  error: string | null;
  busyId: number | null;
  emptyHintKey?: "default" | "product";
  cartStatus: string;
  onStartNewCart: () => void;
  onQtyChange: (line: CartLine, nextQty: number) => void;
  onRemove: (lineId: number) => void;
};

export function CartPanel({
  lines,
  total,
  loading,
  error,
  busyId,
  emptyHintKey = "default",
  cartStatus,
  onStartNewCart,
  onQtyChange,
  onRemove,
}: CartPanelProps) {
  const { t } = useLocale();
  const { formatMoney } = useCurrency();
  const { user, ready: authReady } = useAuth();
  const cartEditable = cartStatus === "cart";
  const needsLogin = authReady && !user;
  const checkoutHref = needsLogin ? "/login?redirect=%2Fcheckout" : "/checkout";
  const checkoutLabel = needsLogin ? t("cart.loginToCheckout") : t("cart.checkout");
  const emptyHint =
    emptyHintKey === "product" ? t("cart.emptyProduct") : t("cart.emptyDefault");

  return (
    <aside
      className="lg:sticky lg:top-24 h-fit space-y-4"
      aria-labelledby="cart-heading"
    >
      <div className="rounded-2xl border border-stone-200 bg-white p-5 shadow-sm">
        <h2
          id="cart-heading"
          className="font-display text-xl font-semibold text-stone-900"
        >
          {t("cart.title")}
        </h2>
        {error ? (
          <p
            className="mt-3 rounded-lg bg-red-50 px-3 py-2 text-sm text-red-800"
            role="alert"
          >
            {error}
          </p>
        ) : null}
        {!loading && !cartEditable && lines.length > 0 ? (
          <div className="mt-3 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-stone-800">
            <p className="font-medium text-amber-950">{t("cart.checkoutComplete")}</p>
            <p className="mt-1 text-stone-600">
              {t("cart.checkoutCompleteHint")}
            </p>
            <button
              type="button"
              className="mt-2 text-xs font-semibold text-amber-900 underline"
              onClick={() => void onStartNewCart()}
            >
              {t("cart.startNewCart")}
            </button>
          </div>
        ) : null}
        {loading ? (
          <p className="mt-4 text-sm text-stone-500">{t("cart.loading")}</p>
        ) : lines.length === 0 ? (
          <p className="mt-4 text-sm text-stone-500">{emptyHint}</p>
        ) : (
          <ul className="mt-4 max-h-[55vh] space-y-4 overflow-y-auto pr-1">
            {lines.map((line) => (
              <li
                key={line.id}
                className="flex gap-3 border-b border-stone-100 pb-4 last:border-0"
              >
                <Link
                  href={`/products/${line.product.id}`}
                  className="relative h-14 w-14 shrink-0 overflow-hidden rounded-lg bg-stone-100 outline-offset-2 hover:opacity-90"
                >
                  {line.product.image_url ? (
                    <Image
                      src={line.product.image_url}
                      alt=""
                      fill
                      className="object-cover"
                      sizes="56px"
                    />
                  ) : null}
                </Link>
                <div className="min-w-0 flex-1">
                  <Link
                    href={`/products/${line.product.id}`}
                    className="truncate font-medium text-stone-900 hover:underline"
                  >
                    {line.product.name}
                  </Link>
                  <p className="text-xs text-stone-500 tabular-nums">
                    @ {formatMoney(parsePrice(line.product))}
                  </p>
                  <div className="mt-2 flex flex-wrap items-center gap-2">
                    <span className="text-xs text-stone-500">{t("cart.qty")}</span>
                    <div className="inline-flex items-center rounded-lg border border-stone-200 bg-stone-50">
                      <button
                        type="button"
                        className="px-2 py-1 text-sm disabled:opacity-40"
                        disabled={!cartEditable || line.quantity <= 1}
                        onClick={() => onQtyChange(line, line.quantity - 1)}
                        aria-label={t("cart.decreaseQty")}
                      >
                        −
                      </button>
                      <input
                        type="number"
                        min={1}
                        max={line.product.stock}
                        value={line.quantity}
                        disabled={!cartEditable}
                        onChange={(ev) => {
                          const v = Number(ev.target.value);
                          if (!Number.isFinite(v)) return;
                          onQtyChange(line, Math.max(1, Math.trunc(v)));
                        }}
                        aria-label={t("cart.qty")}
                        className="w-12 bg-transparent px-1 py-1 text-center text-sm tabular-nums outline-none focus:bg-white"
                      />
                      <button
                        type="button"
                        className="px-2 py-1 text-sm disabled:opacity-40"
                        disabled={
                          !cartEditable ||
                          line.quantity >= line.product.stock
                        }
                        onClick={() => onQtyChange(line, line.quantity + 1)}
                        aria-label={t("cart.increaseQty")}
                      >
                        +
                      </button>
                    </div>
                    <button
                      type="button"
                      className="text-xs font-medium text-red-700 underline-offset-2 hover:underline disabled:opacity-50"
                      disabled={!cartEditable || busyId === line.id}
                      onClick={() => onRemove(line.id)}
                    >
                      {t("common.remove")}
                    </button>
                  </div>
                </div>
                <p className="shrink-0 text-sm font-semibold tabular-nums text-stone-800">
                  {formatMoney(line.line_total)}
                </p>
              </li>
            ))}
          </ul>
        )}
        {!loading && lines.length > 0 ? (
          <div className="mt-4 flex items-center justify-between border-t border-stone-200 pt-4">
            <span className="text-sm font-medium text-stone-600">{t("common.total")}</span>
            <span className="font-display text-xl font-semibold tabular-nums text-amber-950">
              {formatMoney(total)}
            </span>
          </div>
        ) : null}
        {!loading && lines.length > 0 && cartEditable ? (
          <Link
            href={checkoutHref}
            className="mt-4 block w-full rounded-lg bg-stone-900 py-2.5 text-center text-sm font-medium text-white shadow-sm transition hover:bg-stone-800"
          >
            {checkoutLabel}
          </Link>
        ) : null}
      </div>
    </aside>
  );
}
