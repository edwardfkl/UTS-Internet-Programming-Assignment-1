"use client";

import Link from "next/link";
import { useRouter } from "next/navigation";
import { useEffect, useState } from "react";
import { ShopHeader } from "@/components/ShopHeader";
import { useAuth } from "@/contexts/auth-context";
import { useCurrency } from "@/contexts/currency-context";
import { useLocale } from "@/contexts/locale-context";
import { fetchUserOrders } from "@/lib/api";
import type { OrderStatus, UserOrderSummary } from "@/lib/types";

const STATUS_BADGE: Record<OrderStatus, string> = {
  cart: "bg-stone-100 text-stone-700",
  pending_payment: "bg-amber-100 text-amber-900",
  paid: "bg-blue-100 text-blue-900",
  shipped: "bg-indigo-100 text-indigo-900",
  completed: "bg-emerald-100 text-emerald-900",
  cancelled: "bg-red-100 text-red-900",
};

export default function AccountOrdersPage() {
  const router = useRouter();
  const { t, tf } = useLocale();
  const { formatMoney } = useCurrency();
  const { user, ready: authReady } = useAuth();

  const [orders, setOrders] = useState<UserOrderSummary[] | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    if (!authReady) return;
    if (!user) {
      router.replace(
        `/login?redirect=${encodeURIComponent("/account/orders")}`,
      );
      return;
    }
    let cancelled = false;
    void (async () => {
      setLoading(true);
      setError(null);
      try {
        const data = await fetchUserOrders();
        if (!cancelled) setOrders(data);
      } catch (e) {
        if (!cancelled) {
          setError(e instanceof Error ? e.message : t("common.failedToLoad"));
        }
      } finally {
        if (!cancelled) setLoading(false);
      }
    })();
    return () => {
      cancelled = true;
    };
  }, [authReady, user, router, t]);

  if (!authReady || !user) {
    return (
      <div className="min-h-full">
        <ShopHeader />
        <p className="p-8 text-center text-sm text-stone-500">
          {authReady ? t("common.redirectingSignIn") : t("common.loading")}
        </p>
      </div>
    );
  }

  return (
    <div className="min-h-full">
      <ShopHeader tagline={t("accountOrders.tagline")} />
      <main className="mx-auto max-w-5xl px-4 py-8 sm:px-6">
        <nav className="mb-4 text-sm">
          <Link
            href="/account"
            className="font-medium text-amber-900 hover:underline"
          >
            {t("accountOrders.backToAccount")}
          </Link>
        </nav>

        <h1 className="font-display text-2xl font-semibold text-stone-900">
          {t("accountOrders.title")}
        </h1>
        <p className="mt-2 text-sm text-stone-600">
          {t("accountOrders.intro")}
        </p>

        {loading ? (
          <p className="mt-8 text-sm text-stone-500">{t("common.loading")}</p>
        ) : error ? (
          <p
            className="mt-8 rounded-lg bg-red-50 px-3 py-2 text-sm text-red-800"
            role="alert"
          >
            {error}
          </p>
        ) : !orders || orders.length === 0 ? (
          <div className="mt-8 rounded-2xl border border-dashed border-stone-200 bg-stone-50/80 px-4 py-12 text-center text-sm text-stone-600">
            <p>{t("accountOrders.empty")}</p>
            <Link
              href="/"
              className="mt-4 inline-block font-medium text-amber-900 hover:underline"
            >
              {t("accountOrders.startShopping")}
            </Link>
          </div>
        ) : (
          <div className="mt-8 overflow-hidden rounded-2xl border border-stone-200 bg-white shadow-sm">
            <table className="min-w-full divide-y divide-stone-200 text-sm">
              <thead className="bg-stone-50 text-left text-xs font-medium uppercase tracking-wide text-stone-600">
                <tr>
                  <th className="px-4 py-3">
                    {t("accountOrders.col.reference")}
                  </th>
                  <th className="px-4 py-3">{t("accountOrders.col.placed")}</th>
                  <th className="px-4 py-3">{t("accountOrders.col.items")}</th>
                  <th className="px-4 py-3">
                    {t("accountOrders.col.payment")}
                  </th>
                  <th className="px-4 py-3 text-right">
                    {t("accountOrders.col.total")}
                  </th>
                  <th className="px-4 py-3">{t("accountOrders.col.status")}</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-stone-100">
                {orders.map((order) => (
                  <tr key={order.id} className="hover:bg-stone-50/70">
                    <td className="whitespace-nowrap px-4 py-3 font-medium text-stone-900">
                      {order.reference}
                    </td>
                    <td className="whitespace-nowrap px-4 py-3 text-stone-700">
                      {order.placed_at
                        ? new Date(order.placed_at).toLocaleString()
                        : "—"}
                    </td>
                    <td className="whitespace-nowrap px-4 py-3 tabular-nums text-stone-700">
                      {tf("accountOrders.itemCount", {
                        count: order.items_count,
                      })}
                    </td>
                    <td className="whitespace-nowrap px-4 py-3 text-stone-700">
                      {order.payment_method
                        ? t(`checkout.pay.${order.payment_method}.label`)
                        : "—"}
                    </td>
                    <td className="whitespace-nowrap px-4 py-3 text-right tabular-nums text-stone-900">
                      {formatMoney(order.total_amount)}
                    </td>
                    <td className="whitespace-nowrap px-4 py-3">
                      <span
                        className={`inline-flex rounded-full px-2 py-0.5 text-xs font-semibold ${
                          STATUS_BADGE[order.status] ?? "bg-stone-100"
                        }`}
                      >
                        {t(`accountOrders.status.${order.status}`)}
                      </span>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}
      </main>
    </div>
  );
}
