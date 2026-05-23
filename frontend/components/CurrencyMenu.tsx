"use client";

import { useCallback, useEffect, useId, useRef, useState } from "react";
import { useCurrency } from "@/contexts/currency-context";
import { useLocale } from "@/contexts/locale-context";
import {
  CURRENCY_LABELS,
  SUPPORTED_CURRENCIES,
  type AppCurrency,
} from "@/lib/currencies";

function CurrencyIcon({ className }: { className?: string }) {
  return (
    <svg
      className={className}
      viewBox="0 0 24 24"
      fill="none"
      stroke="currentColor"
      strokeWidth={1.5}
      aria-hidden
    >
      <circle cx="12" cy="12" r="8" />
      <path strokeLinecap="round" d="M12 8v8M9 10h4.5a1.5 1.5 0 010 3H9m6 1h-4.5a1.5 1.5 0 000 3H15" />
    </svg>
  );
}

export function CurrencyMenu() {
  const { currency, setCurrency } = useCurrency();
  const { t } = useLocale();
  const [open, setOpen] = useState(false);
  const rootRef = useRef<HTMLDivElement>(null);
  const menuId = useId();

  const close = useCallback(() => setOpen(false), []);

  useEffect(() => {
    if (!open) return;
    function onPointerDown(e: MouseEvent): void {
      const el = rootRef.current;
      if (!el || el.contains(e.target as Node)) return;
      close();
    }
    function onKey(e: KeyboardEvent): void {
      if (e.key === "Escape") close();
    }
    document.addEventListener("mousedown", onPointerDown);
    document.addEventListener("keydown", onKey);
    return () => {
      document.removeEventListener("mousedown", onPointerDown);
      document.removeEventListener("keydown", onKey);
    };
  }, [open, close]);

  return (
    <div ref={rootRef} className="relative z-20">
      <button
        type="button"
        className="flex h-9 min-w-9 shrink-0 items-center justify-center gap-1 rounded-full border border-stone-200 bg-white px-2 text-stone-700 shadow-sm ring-offset-2 outline-none hover:bg-stone-50 focus-visible:ring-2 focus-visible:ring-amber-700 sm:px-2.5"
        aria-expanded={open}
        aria-haspopup="menu"
        aria-controls={open ? menuId : undefined}
        onClick={() => setOpen((v) => !v)}
        title={t("currency.title")}
      >
        <span className="sr-only">{t("common.openCurrencyMenu")}</span>
        <CurrencyIcon className="hidden h-4 w-4 sm:block" />
        <span className="text-xs font-semibold tabular-nums">{currency}</span>
      </button>

      {open ? (
        <div
          id={menuId}
          role="menu"
          aria-label={t("currency.title")}
          className="absolute right-0 mt-2 max-h-[min(20rem,70vh)] min-w-[10.5rem] overflow-y-auto rounded-xl border border-stone-200 bg-white py-1 shadow-lg"
        >
          {SUPPORTED_CURRENCIES.map((code) => (
            <button
              key={code}
              type="button"
              role="menuitemradio"
              aria-checked={currency === code}
              className={`w-full px-4 py-2.5 text-left text-sm ${
                currency === code
                  ? "bg-amber-50 font-medium text-amber-950"
                  : "text-stone-800 hover:bg-stone-50"
              }`}
              onClick={() => {
                setCurrency(code as AppCurrency);
                close();
              }}
            >
              {CURRENCY_LABELS[code]}
            </button>
          ))}
        </div>
      ) : null}
    </div>
  );
}
