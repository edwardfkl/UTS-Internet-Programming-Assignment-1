"use client";

import Link from "next/link";
import { useRouter, useSearchParams } from "next/navigation";
import { Suspense, useEffect, useState } from "react";
import { ShopHeader } from "@/components/ShopHeader";
import { useAuth } from "@/contexts/auth-context";

function safeRedirect(path: string | null): string {
  if (!path || !path.startsWith("/")) return "/";
  if (path.startsWith("//")) return "/";
  return path;
}

function RegisterContent() {
  const router = useRouter();
  const searchParams = useSearchParams();
  const redirectTo = safeRedirect(searchParams.get("redirect"));
  const { register, user, ready } = useAuth();
  const [name, setName] = useState("");
  const [email, setEmail] = useState("");
  const [password, setPassword] = useState("");
  const [passwordConfirmation, setPasswordConfirmation] = useState("");
  const [error, setError] = useState<string | null>(null);
  const [busy, setBusy] = useState(false);

  useEffect(() => {
    if (ready && user) router.replace(redirectTo);
  }, [ready, user, router, redirectTo]);

  async function onSubmit(e: React.FormEvent): Promise<void> {
    e.preventDefault();
    setError(null);
    setBusy(true);
    try {
      await register(name, email, password, passwordConfirmation);
      router.replace(redirectTo);
    } catch (err) {
      setError(err instanceof Error ? err.message : "Registration failed");
    } finally {
      setBusy(false);
    }
  }

  if (ready && user) {
    return (
      <div className="min-h-full">
        <ShopHeader />
        <p className="p-8 text-center text-sm text-stone-500">Redirecting…</p>
      </div>
    );
  }

  const loginHref =
    redirectTo !== "/" ? `/login?redirect=${encodeURIComponent(redirectTo)}` : "/login";

  return (
    <div className="min-h-full">
      <ShopHeader />
      <main className="mx-auto max-w-md px-4 py-12 sm:px-6">
        <h1 className="font-display text-2xl font-semibold text-stone-900">Create account</h1>
        <p className="mt-2 text-sm text-stone-600">
          Password must be at least 8 characters. This is a coursework API — use a unique
          test password.
        </p>
        <form onSubmit={(e) => void onSubmit(e)} className="mt-8 space-y-4">
          <div>
            <label htmlFor="name" className="block text-sm font-medium text-stone-700">
              Name
            </label>
            <input
              id="name"
              type="text"
              autoComplete="name"
              required
              value={name}
              onChange={(ev) => setName(ev.target.value)}
              className="mt-1 w-full rounded-lg border border-stone-200 px-3 py-2 text-stone-900 shadow-sm"
            />
          </div>
          <div>
            <label htmlFor="email" className="block text-sm font-medium text-stone-700">
              Email
            </label>
            <input
              id="email"
              type="email"
              autoComplete="email"
              required
              value={email}
              onChange={(ev) => setEmail(ev.target.value)}
              className="mt-1 w-full rounded-lg border border-stone-200 px-3 py-2 text-stone-900 shadow-sm"
            />
          </div>
          <div>
            <label htmlFor="password" className="block text-sm font-medium text-stone-700">
              Password
            </label>
            <input
              id="password"
              type="password"
              autoComplete="new-password"
              required
              value={password}
              onChange={(ev) => setPassword(ev.target.value)}
              className="mt-1 w-full rounded-lg border border-stone-200 px-3 py-2 text-stone-900 shadow-sm"
            />
          </div>
          <div>
            <label
              htmlFor="password_confirmation"
              className="block text-sm font-medium text-stone-700"
            >
              Confirm password
            </label>
            <input
              id="password_confirmation"
              type="password"
              autoComplete="new-password"
              required
              value={passwordConfirmation}
              onChange={(ev) => setPasswordConfirmation(ev.target.value)}
              className="mt-1 w-full rounded-lg border border-stone-200 px-3 py-2 text-stone-900 shadow-sm"
            />
          </div>
          {error ? (
            <p className="text-sm text-red-800" role="alert">
              {error}
            </p>
          ) : null}
          <button
            type="submit"
            disabled={busy}
            className="w-full rounded-lg bg-amber-800 py-2.5 text-sm font-medium text-white hover:bg-amber-900 disabled:opacity-50"
          >
            {busy ? "Creating account…" : "Register"}
          </button>
        </form>
        <p className="mt-6 text-center text-sm text-stone-600">
          Already have an account?{" "}
          <Link href={loginHref} className="font-medium text-amber-900 hover:underline">
            Log in
          </Link>
        </p>
      </main>
    </div>
  );
}

export default function RegisterPage() {
  return (
    <Suspense
      fallback={
        <div className="min-h-full">
          <ShopHeader />
          <p className="p-8 text-center text-sm text-stone-500">Loading…</p>
        </div>
      }
    >
      <RegisterContent />
    </Suspense>
  );
}
