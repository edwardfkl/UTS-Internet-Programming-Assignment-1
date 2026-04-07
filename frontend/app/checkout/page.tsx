"use client";

import Link from "next/link";
import { useRouter } from "next/navigation";
import { useEffect, useState } from "react";
import { ShopHeader } from "@/components/ShopHeader";
import { useAuth } from "@/contexts/auth-context";
import { useCart } from "@/hooks/useCart";
import { placeOrder, resetCartSession } from "@/lib/api";
import { money } from "@/lib/money";
import {
  PAYMENT_OPTIONS,
  paymentDetailBlocks,
} from "@/lib/paymentInstructions";
import { fetchProfile } from "@/lib/profileApi";
import type { CheckoutResult, PaymentMethod, ShippingForm } from "@/lib/types";

export default function CheckoutPage() {
  const router = useRouter();
  const { user, ready: authReady } = useAuth();
  const {
    cartLines,
    cartTotal,
    cartLoading,
    cartToken,
    cartStatus,
    startNewCart,
  } = useCart();

  const [profileReady, setProfileReady] = useState(false);
  const [method, setMethod] = useState<PaymentMethod>("atm_transfer");
  const [submitting, setSubmitting] = useState(false);
  const [formError, setFormError] = useState<string | null>(null);
  const [done, setDone] = useState<CheckoutResult | null>(null);
  const [saveToProfile, setSaveToProfile] = useState(true);

  const [shipping, setShipping] = useState<ShippingForm>({
    recipient_name: "",
    phone: "",
    line1: "",
    line2: "",
    city: "",
    state: "",
    postcode: "",
    country: "Australia",
  });

  useEffect(() => {
    if (!authReady) return;
    if (!user) {
      router.replace(`/login?redirect=${encodeURIComponent("/checkout")}`);
      return;
    }
    let cancelled = false;
    void (async () => {
      try {
        const p = await fetchProfile();
        if (cancelled) return;
        setShipping((prev) => ({
          ...prev,
          recipient_name: p.shipping_recipient_name?.trim() || p.name || "",
          phone: p.phone?.trim() || "",
          line1: p.shipping_line1?.trim() || "",
          line2: p.shipping_line2?.trim() || "",
          city: p.shipping_city?.trim() || "",
          state: p.shipping_state?.trim() || "",
          postcode: p.shipping_postcode?.trim() || "",
          country: p.shipping_country?.trim() || "Australia",
        }));
      } catch {
        /* keep empty form */
      } finally {
        if (!cancelled) setProfileReady(true);
      }
    })();
    return () => {
      cancelled = true;
    };
  }, [authReady, user, router]);

  useEffect(() => {
    if (
      cartLoading ||
      done ||
      cartLines.length > 0 ||
      cartStatus !== "cart"
    ) {
      return;
    }
    router.replace("/");
  }, [cartLoading, cartLines.length, done, cartStatus, router]);

  async function onSubmit(e: React.FormEvent): Promise<void> {
    e.preventDefault();
    setFormError(null);
    if (!cartToken || cartLines.length === 0 || !user) return;
    setSubmitting(true);
    try {
      const result = await placeOrder(cartToken, method, shipping, saveToProfile);
      setDone(result);
      await resetCartSession();
    } catch (err) {
      setFormError(
        err instanceof Error ? err.message : "Could not complete checkout",
      );
    } finally {
      setSubmitting(false);
    }
  }

  if (!authReady || !user) {
    return (
      <div className="min-h-full">
        <ShopHeader />
        <p className="p-8 text-center text-sm text-stone-500">
          {authReady ? "Redirecting to sign in…" : "Loading…"}
        </p>
      </div>
    );
  }

  if (done) {
    const blocks = paymentDetailBlocks(
      done.payment_method,
      done.order_reference,
      money.format(done.total),
    );
    const s = done.shipping;
    return (
      <div className="min-h-full">
        <ShopHeader tagline="Order confirmation" />
        <main className="mx-auto max-w-2xl px-4 py-10 sm:px-6">
          <p className="text-xs font-medium uppercase tracking-widest text-amber-800">
            Thank you
          </p>
          <h1 className="font-display mt-2 text-3xl font-semibold text-stone-900">
            Your order is pending payment
          </h1>
          <p className="mt-2 text-sm text-stone-600">
            Order <strong>{done.order_reference}</strong> — use the details below
            (coursework demo — not real banking credentials).
          </p>

          <section className="mt-8 rounded-2xl border border-stone-200 bg-white p-5 shadow-sm">
            <h2 className="font-display text-lg font-semibold text-stone-900">Ship to</h2>
            <p className="mt-3 text-sm text-stone-800">
              {s.recipient_name}
              <br />
              {s.line1}
              {s.line2 ? (
                <>
                  <br />
                  {s.line2}
                </>
              ) : null}
              <br />
              {s.city} {s.state} {s.postcode}
              <br />
              {s.country}
              <br />
              <span className="text-stone-600">Phone: {s.phone}</span>
            </p>
          </section>

          <ul className="mt-6 space-y-2 border-t border-stone-200 pt-6 text-sm text-stone-700">
            {done.lines.map((line) => (
              <li
                key={`${line.name}-${line.quantity}`}
                className="flex justify-between gap-4"
              >
                <span>
                  {line.name} × {line.quantity}
                </span>
                <span className="tabular-nums">{money.format(line.line_total)}</span>
              </li>
            ))}
            <li className="flex justify-between border-t border-stone-200 pt-3 font-medium text-stone-900">
              <span>Total due</span>
              <span className="tabular-nums">{money.format(done.total)}</span>
            </li>
          </ul>
          <div className="mt-8 space-y-6">
            {blocks.map((block) => (
              <section
                key={block.title}
                className="rounded-2xl border border-stone-200 bg-white p-5 shadow-sm"
              >
                <h2 className="font-display text-lg font-semibold text-stone-900">
                  {block.title}
                </h2>
                <dl className="mt-4 space-y-3 text-sm">
                  {block.lines.map((row) => (
                    <div key={row.label}>
                      <dt className="text-stone-500">{row.label}</dt>
                      <dd className="mt-0.5 font-medium text-stone-900 break-all">
                        {row.value}
                      </dd>
                    </div>
                  ))}
                </dl>
              </section>
            ))}
          </div>
          <p className="mt-8 text-xs text-stone-500">
            No payment gateway is connected. Tutors can verify the order row in the
            database: status <code className="rounded bg-stone-100 px-1">pending_payment</code>
            , <code className="rounded bg-stone-100 px-1">shipping_*</code>, and{" "}
            <code className="rounded bg-stone-100 px-1">user_id</code>.
          </p>
          <div className="mt-8 flex flex-wrap gap-3">
            <Link
              href="/"
              className="inline-flex rounded-lg bg-amber-800 px-4 py-2.5 text-sm font-medium text-white hover:bg-amber-900"
            >
              Continue shopping
            </Link>
          </div>
        </main>
      </div>
    );
  }

  if (!cartLoading && cartStatus === "pending_payment") {
    return (
      <div className="min-h-full">
        <ShopHeader />
        <main className="mx-auto max-w-lg px-4 py-12 sm:px-6">
          <h1 className="font-display text-2xl font-semibold text-stone-900">
            Active order on this browser
          </h1>
          <p className="mt-2 text-sm text-stone-600">
            This cart token is already linked to a submitted order (awaiting payment).
            Start a new cart to place another order.
          </p>
          <button
            type="button"
            className="mt-6 rounded-lg bg-amber-800 px-4 py-2 text-sm font-medium text-white hover:bg-amber-900"
            onClick={() => void startNewCart()}
          >
            Start new cart
          </button>
          <Link
            href="/"
            className="mt-4 block text-sm font-medium text-amber-900 hover:underline"
          >
            Back to catalogue
          </Link>
        </main>
      </div>
    );
  }

  return (
    <div className="min-h-full">
      <ShopHeader tagline="Checkout" />
      <main className="mx-auto max-w-3xl px-4 py-8 sm:px-6">
        <h1 className="font-display text-3xl font-semibold text-stone-900">
          Checkout
        </h1>
        <p className="mt-2 text-sm text-stone-600">
          You are signed in as <strong>{user.email}</strong>. Delivery details are required
          before payment.
        </p>
        <p className="mt-2 text-xs text-stone-500">
          <Link href="/account" className="font-medium text-amber-900 hover:underline">
            Edit saved profile & address
          </Link>
        </p>

        {cartLoading || !profileReady ? (
          <p className="mt-8 text-stone-500">Loading…</p>
        ) : (
          <form onSubmit={(e) => void onSubmit(e)} className="mt-8 space-y-8">
            <section className="rounded-2xl border border-stone-200 bg-white p-5 shadow-sm">
              <h2 className="font-display text-lg font-semibold text-stone-900">
                Order summary
              </h2>
              <ul className="mt-4 divide-y divide-stone-100 text-sm">
                {cartLines.map((line) => (
                  <li
                    key={line.id}
                    className="flex justify-between gap-4 py-3 first:pt-0"
                  >
                    <span className="text-stone-700">
                      {line.product.name} × {line.quantity}
                    </span>
                    <span className="tabular-nums text-stone-900">
                      {money.format(line.line_total)}
                    </span>
                  </li>
                ))}
              </ul>
              <div className="mt-4 flex justify-between border-t border-stone-200 pt-4 text-base font-semibold">
                <span>Total</span>
                <span className="tabular-nums">{money.format(cartTotal)}</span>
              </div>
            </section>

            <section className="rounded-2xl border border-stone-200 bg-white p-5 shadow-sm">
              <h2 className="font-display text-lg font-semibold text-stone-900">
                Delivery address
              </h2>
              <div className="mt-4 grid gap-4 sm:grid-cols-2">
                <div className="sm:col-span-2">
                  <label className="block text-sm font-medium text-stone-700">
                    Recipient name
                  </label>
                  <input
                    required
                    value={shipping.recipient_name}
                    onChange={(e) =>
                      setShipping((s) => ({ ...s, recipient_name: e.target.value }))
                    }
                    className="mt-1 w-full rounded-lg border border-stone-200 px-3 py-2"
                  />
                </div>
                <div className="sm:col-span-2">
                  <label className="block text-sm font-medium text-stone-700">Phone</label>
                  <input
                    required
                    type="tel"
                    value={shipping.phone}
                    onChange={(e) => setShipping((s) => ({ ...s, phone: e.target.value }))}
                    className="mt-1 w-full rounded-lg border border-stone-200 px-3 py-2"
                  />
                </div>
                <div className="sm:col-span-2">
                  <label className="block text-sm font-medium text-stone-700">
                    Address line 1
                  </label>
                  <input
                    required
                    value={shipping.line1}
                    onChange={(e) => setShipping((s) => ({ ...s, line1: e.target.value }))}
                    className="mt-1 w-full rounded-lg border border-stone-200 px-3 py-2"
                  />
                </div>
                <div className="sm:col-span-2">
                  <label className="block text-sm font-medium text-stone-700">
                    Address line 2 (optional)
                  </label>
                  <input
                    value={shipping.line2}
                    onChange={(e) => setShipping((s) => ({ ...s, line2: e.target.value }))}
                    className="mt-1 w-full rounded-lg border border-stone-200 px-3 py-2"
                  />
                </div>
                <div>
                  <label className="block text-sm font-medium text-stone-700">
                    City / suburb
                  </label>
                  <input
                    required
                    value={shipping.city}
                    onChange={(e) => setShipping((s) => ({ ...s, city: e.target.value }))}
                    className="mt-1 w-full rounded-lg border border-stone-200 px-3 py-2"
                  />
                </div>
                <div>
                  <label className="block text-sm font-medium text-stone-700">
                    State / territory
                  </label>
                  <input
                    required
                    value={shipping.state}
                    onChange={(e) => setShipping((s) => ({ ...s, state: e.target.value }))}
                    className="mt-1 w-full rounded-lg border border-stone-200 px-3 py-2"
                  />
                </div>
                <div>
                  <label className="block text-sm font-medium text-stone-700">Postcode</label>
                  <input
                    required
                    value={shipping.postcode}
                    onChange={(e) =>
                      setShipping((s) => ({ ...s, postcode: e.target.value }))
                    }
                    className="mt-1 w-full rounded-lg border border-stone-200 px-3 py-2"
                  />
                </div>
                <div>
                  <label className="block text-sm font-medium text-stone-700">Country</label>
                  <input
                    required
                    value={shipping.country}
                    onChange={(e) =>
                      setShipping((s) => ({ ...s, country: e.target.value }))
                    }
                    className="mt-1 w-full rounded-lg border border-stone-200 px-3 py-2"
                  />
                </div>
              </div>
              <label className="mt-4 flex cursor-pointer items-center gap-2 text-sm text-stone-700">
                <input
                  type="checkbox"
                  checked={saveToProfile}
                  onChange={(e) => setSaveToProfile(e.target.checked)}
                />
                Save this phone & address to my account profile
              </label>
            </section>

            <section className="rounded-2xl border border-stone-200 bg-white p-5 shadow-sm">
              <h2 className="font-display text-lg font-semibold text-stone-900">
                Payment method
              </h2>
              <fieldset className="mt-4 space-y-3">
                <legend className="sr-only">Select payment method</legend>
                {PAYMENT_OPTIONS.map((opt) => (
                  <label
                    key={opt.id}
                    className={`flex cursor-pointer gap-3 rounded-xl border p-4 transition ${
                      method === opt.id
                        ? "border-amber-800 bg-amber-50/50"
                        : "border-stone-200 hover:bg-stone-50"
                    }`}
                  >
                    <input
                      type="radio"
                      name="payment"
                      value={opt.id}
                      checked={method === opt.id}
                      onChange={() => setMethod(opt.id)}
                      className="mt-1"
                    />
                    <span>
                      <span className="block font-medium text-stone-900">
                        {opt.label}
                      </span>
                      <span className="mt-1 block text-xs text-stone-600">
                        {opt.description}
                      </span>
                    </span>
                  </label>
                ))}
              </fieldset>
            </section>

            {formError ? (
              <p className="rounded-lg bg-red-50 px-3 py-2 text-sm text-red-800" role="alert">
                {formError}
              </p>
            ) : null}

            <div className="flex flex-wrap gap-3">
              <button
                type="submit"
                disabled={
                  submitting ||
                  cartLines.length === 0 ||
                  cartStatus !== "cart"
                }
                className="rounded-lg bg-amber-800 px-5 py-2.5 text-sm font-medium text-white shadow-sm hover:bg-amber-900 disabled:cursor-not-allowed disabled:opacity-50"
              >
                {submitting ? "Submitting…" : "Place order"}
              </button>
              <Link
                href="/"
                className="rounded-lg border border-stone-300 px-5 py-2.5 text-sm font-medium text-stone-800 hover:bg-stone-50"
              >
                Cancel
              </Link>
            </div>
          </form>
        )}
      </main>
    </div>
  );
}
