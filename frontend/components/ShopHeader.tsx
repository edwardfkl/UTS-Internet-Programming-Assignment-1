"use client";

import Link from "next/link";
import { CurrencyMenu } from "@/components/CurrencyMenu";
import { LanguageMenu } from "@/components/LanguageMenu";
import { UserAvatarMenu } from "@/components/UserAvatarMenu";
import { useAuth } from "@/contexts/auth-context";
import { useLocale } from "@/contexts/locale-context";

type ShopHeaderProps = {
  tagline?: string;
};

export function ShopHeader({ tagline }: ShopHeaderProps) {
  const { user, ready, logout } = useAuth();
  const { t } = useLocale();

  return (
    <header className="border-b border-stone-200 bg-white/80 backdrop-blur-sm sticky top-0 z-10">
      <div className="mx-auto flex max-w-6xl flex-wrap items-baseline justify-between gap-4 px-4 py-5 sm:px-6">
        <div>
          <p className="text-xs font-medium uppercase tracking-[0.2em] text-amber-800/80">
            {t("common.onlineStore")}
          </p>
          <h1 className="font-display text-2xl font-semibold text-stone-900 sm:text-3xl">
            <Link href="/" className="hover:text-amber-950 transition-colors">
              {t("common.storeName")}
            </Link>
          </h1>
        </div>
        <nav className="flex flex-wrap items-center gap-x-3 gap-y-2 text-sm text-stone-600 sm:gap-x-4">
          <Link href="/" className="font-medium text-stone-800 hover:text-amber-900">
            {t("nav.catalog")}
          </Link>
          <CurrencyMenu />
          <LanguageMenu />
          {ready && user ? (
            <>
              <span className="hidden text-stone-400 sm:inline" aria-hidden>
                |
              </span>
              <UserAvatarMenu user={user} onLogout={logout} />
            </>
          ) : (
            <Link
              href="/login"
              className={`font-medium hover:text-amber-900 ${ready ? "text-stone-800" : "text-stone-400"}`}
            >
              {t("nav.logIn")}
            </Link>
          )}
          {tagline ? (
            <>
              <span className="hidden text-stone-400 sm:inline" aria-hidden>
                ·
              </span>
              <span className="hidden max-w-[220px] truncate sm:inline">{tagline}</span>
            </>
          ) : null}
        </nav>
      </div>
    </header>
  );
}
