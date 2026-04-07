"use client";

import Link from "next/link";
import { useAuth } from "@/contexts/auth-context";

type ShopHeaderProps = {
  tagline?: string;
};

export function ShopHeader({ tagline }: ShopHeaderProps) {
  const { user, ready, logout } = useAuth();

  return (
    <header className="border-b border-stone-200 bg-white/80 backdrop-blur-sm sticky top-0 z-10">
      <div className="mx-auto flex max-w-6xl flex-wrap items-baseline justify-between gap-4 px-4 py-5 sm:px-6">
        <div>
          <p className="text-xs font-medium uppercase tracking-[0.2em] text-amber-800/80">
            Internet Programming
          </p>
          <h1 className="font-display text-2xl font-semibold text-stone-900 sm:text-3xl">
            <Link href="/" className="hover:text-amber-950 transition-colors">
              Studio Supply Co.
            </Link>
          </h1>
        </div>
        <nav className="flex flex-wrap items-center gap-x-4 gap-y-2 text-sm text-stone-600">
          <Link href="/" className="font-medium text-stone-800 hover:text-amber-900">
            Catalog
          </Link>
          <Link
            href="/checkout"
            className="font-medium text-stone-800 hover:text-amber-900"
          >
            Checkout
          </Link>
          {ready && user ? (
            <>
              <span className="text-stone-400" aria-hidden>
                |
              </span>
              <Link
                href="/account"
                className="font-medium text-stone-800 hover:text-amber-900"
              >
                Account
              </Link>
              <span className="max-w-[140px] truncate text-stone-700" title={user.email}>
                {user.name}
              </span>
              <button
                type="button"
                onClick={() => void logout()}
                className="font-medium text-amber-900 hover:underline"
              >
                Log out
              </button>
            </>
          ) : (
            <>
              <Link
                href="/login"
                className={`font-medium hover:text-amber-900 ${ready ? "text-stone-800" : "text-stone-400"}`}
              >
                Log in
              </Link>
              <Link
                href="/register"
                className={`font-medium hover:underline ${ready ? "text-amber-900" : "text-stone-400"}`}
              >
                Register
              </Link>
            </>
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
