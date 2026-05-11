"use client";

import Link from "next/link";
import { useRouter } from "next/navigation";
import { useEffect, useState } from "react";
import { ShopHeader } from "@/components/ShopHeader";
import { useAuth } from "@/contexts/auth-context";
import { useLocale } from "@/contexts/locale-context";
import { changePassword } from "@/lib/passwordApi";
import { fetchProfile, updateProfile } from "@/lib/profileApi";
import type { UserProfile } from "@/lib/types";

export default function AccountPage() {
  const router = useRouter();
  const { t } = useLocale();
  const { user, ready, logout, refreshUser } = useAuth();
  const [profile, setProfile] = useState<UserProfile | null>(null);
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [ok, setOk] = useState(false);
  const [currentPassword, setCurrentPassword] = useState("");
  const [newPassword, setNewPassword] = useState("");
  const [newPasswordConfirm, setNewPasswordConfirm] = useState("");
  const [pwdSaving, setPwdSaving] = useState(false);
  const [pwdError, setPwdError] = useState<string | null>(null);
  const [pwdOk, setPwdOk] = useState(false);

  const [name, setName] = useState("");
  const [avatarUrl, setAvatarUrl] = useState("");
  const [phone, setPhone] = useState("");
  const [shippingRecipient, setShippingRecipient] = useState("");
  const [line1, setLine1] = useState("");
  const [line2, setLine2] = useState("");
  const [city, setCity] = useState("");
  const [state, setState] = useState("");
  const [postcode, setPostcode] = useState("");
  const [country, setCountry] = useState("Australia");

  useEffect(() => {
    if (ready && !user) router.replace(`/login?redirect=${encodeURIComponent("/account")}`);
  }, [ready, user, router]);

  useEffect(() => {
    if (!ready || !user) return;
    let cancelled = false;
    void (async () => {
      setLoading(true);
      setError(null);
      try {
        const p = await fetchProfile();
        if (cancelled) return;
        setProfile(p);
        setName(p.name);
        setAvatarUrl(p.avatar_url ?? "");
        setPhone(p.phone ?? "");
        setShippingRecipient(p.shipping_recipient_name ?? p.name);
        setLine1(p.shipping_line1 ?? "");
        setLine2(p.shipping_line2 ?? "");
        setCity(p.shipping_city ?? "");
        setState(p.shipping_state ?? "");
        setPostcode(p.shipping_postcode ?? "");
        setCountry(p.shipping_country ?? "Australia");
      } catch (e) {
        if (!cancelled) setError(e instanceof Error ? e.message : t("common.failedToLoad"));
      } finally {
        if (!cancelled) setLoading(false);
      }
    })();
    return () => {
      cancelled = true;
    };
  }, [ready, user, t]);

  useEffect(() => {
    if (typeof window === "undefined" || loading) return;

    function scrollToPassword(): void {
      if (window.location.hash !== "#change-password") return;
      requestAnimationFrame(() => {
        document.getElementById("change-password")?.scrollIntoView({
          behavior: "smooth",
          block: "start",
        });
      });
    }

    scrollToPassword();
    window.addEventListener("hashchange", scrollToPassword);
    return () => window.removeEventListener("hashchange", scrollToPassword);
  }, [loading]);

  async function onSubmit(e: React.FormEvent): Promise<void> {
    e.preventDefault();
    setError(null);
    setOk(false);
    setSaving(true);
    try {
      const p = await updateProfile({
        name,
        avatar_url: avatarUrl.trim() || null,
        phone: phone || null,
        shipping_recipient_name: shippingRecipient || null,
        shipping_line1: line1 || null,
        shipping_line2: line2 || null,
        shipping_city: city || null,
        shipping_state: state || null,
        shipping_postcode: postcode || null,
        shipping_country: country || null,
      });
      setProfile(p);
      setAvatarUrl(p.avatar_url ?? "");
      setOk(true);
      await refreshUser();
    } catch (err) {
      setError(err instanceof Error ? err.message : t("common.saveFailed"));
    } finally {
      setSaving(false);
    }
  }

  async function onPasswordSubmit(e: React.FormEvent): Promise<void> {
    e.preventDefault();
    setPwdError(null);
    setPwdOk(false);
    setPwdSaving(true);
    try {
      await changePassword({
        currentPassword,
        password: newPassword,
        passwordConfirmation: newPasswordConfirm,
      });
      setCurrentPassword("");
      setNewPassword("");
      setNewPasswordConfirm("");
      setPwdOk(true);
    } catch (err) {
      setPwdError(
        err instanceof Error ? err.message : t("common.couldNotChangePassword"),
      );
    } finally {
      setPwdSaving(false);
    }
  }

  if (!ready || !user) {
    return (
      <div className="min-h-full">
        <ShopHeader />
        <p className="p-8 text-center text-sm text-stone-500">{t("common.loading")}</p>
      </div>
    );
  }

  return (
    <div className="min-h-full">
      <ShopHeader tagline={t("common.taglineProfile")} />
      <main className="mx-auto max-w-2xl px-4 py-8 sm:px-6">
        <div className="flex flex-wrap items-center justify-between gap-4">
          <h1 className="font-display text-3xl font-semibold text-stone-900">
            {t("account.title")}
          </h1>
          <p className="text-sm text-stone-600">
            {t("account.signedInAs")}{" "}
            <span className="font-medium text-stone-800">{user.email}</span>
          </p>
        </div>
        <p className="mt-2 text-sm text-stone-600">{t("account.intro")}</p>
        <p className="mt-3 text-sm">
          <Link
            href="/account/orders"
            className="font-medium text-amber-900 hover:underline"
          >
            {t("account.viewOrderHistory")}
          </Link>
        </p>

        {loading ? (
          <p className="mt-8 text-stone-500">{t("common.loadingProfile")}</p>
        ) : (
          <div className="mt-8 space-y-8">
            <form
              id="change-password"
              onSubmit={(e) => void onPasswordSubmit(e)}
              className="scroll-mt-28 rounded-2xl border border-stone-200 bg-white p-5 shadow-sm"
            >
              <h2 className="font-display text-lg font-semibold text-stone-900">
                {t("account.changePassword")}
              </h2>
              <p className="mt-1 text-sm text-stone-600">
                {t("account.changePasswordHint")}
              </p>
              <div className="mt-4 grid max-w-md gap-4">
                <div>
                  <label htmlFor="current-pw" className="block text-sm font-medium text-stone-700">
                    {t("account.currentPassword")}
                  </label>
                  <input
                    id="current-pw"
                    type="password"
                    autoComplete="current-password"
                    value={currentPassword}
                    onChange={(e) => setCurrentPassword(e.target.value)}
                    required
                    className="mt-1 w-full rounded-lg border border-stone-200 px-3 py-2"
                  />
                </div>
                <div>
                  <label htmlFor="new-pw" className="block text-sm font-medium text-stone-700">
                    {t("account.newPassword")}
                  </label>
                  <input
                    id="new-pw"
                    type="password"
                    autoComplete="new-password"
                    value={newPassword}
                    onChange={(e) => setNewPassword(e.target.value)}
                    required
                    minLength={8}
                    className="mt-1 w-full rounded-lg border border-stone-200 px-3 py-2"
                  />
                </div>
                <div>
                  <label htmlFor="new-pw2" className="block text-sm font-medium text-stone-700">
                    {t("account.confirmNewPassword")}
                  </label>
                  <input
                    id="new-pw2"
                    type="password"
                    autoComplete="new-password"
                    value={newPasswordConfirm}
                    onChange={(e) => setNewPasswordConfirm(e.target.value)}
                    required
                    minLength={8}
                    className="mt-1 w-full rounded-lg border border-stone-200 px-3 py-2"
                  />
                </div>
              </div>
              {pwdError ? (
                <p className="mt-3 text-sm text-red-800" role="alert">
                  {pwdError}
                </p>
              ) : null}
              {pwdOk ? (
                <p className="mt-3 text-sm text-green-800">{t("account.passwordUpdated")}</p>
              ) : null}
              <button
                type="submit"
                disabled={pwdSaving}
                className="mt-4 rounded-lg border border-stone-300 bg-stone-50 px-5 py-2.5 text-sm font-medium text-stone-900 hover:bg-stone-100 disabled:opacity-50"
              >
                {pwdSaving ? t("account.updatingPassword") : t("account.updatePassword")}
              </button>
            </form>

            <form onSubmit={(e) => void onSubmit(e)} className="space-y-8">
            <section className="rounded-2xl border border-stone-200 bg-white p-5 shadow-sm">
              <h2 className="font-display text-lg font-semibold text-stone-900">
                {t("account.personal")}
              </h2>
              <div className="mt-4 grid gap-4 sm:grid-cols-2">
                <div className="sm:col-span-2">
                  <label htmlFor="name" className="block text-sm font-medium text-stone-700">
                    {t("account.fullName")}
                  </label>
                  <input
                    id="name"
                    value={name}
                    onChange={(e) => setName(e.target.value)}
                    required
                    className="mt-1 w-full rounded-lg border border-stone-200 px-3 py-2"
                  />
                </div>
                <div className="sm:col-span-2">
                  <label htmlFor="avatar-url" className="block text-sm font-medium text-stone-700">
                    {t("account.profilePhotoUrl")}
                  </label>
                  <input
                    id="avatar-url"
                    type="url"
                    inputMode="url"
                    placeholder="https://example.com/photo.jpg"
                    value={avatarUrl}
                    onChange={(e) => setAvatarUrl(e.target.value)}
                    className="mt-1 w-full rounded-lg border border-stone-200 px-3 py-2"
                  />
                  <p className="mt-1 text-xs text-stone-500">{t("account.avatarHint")}</p>
                </div>
                <div>
                  <label htmlFor="email-ro" className="block text-sm font-medium text-stone-700">
                    {t("common.email")}
                  </label>
                  <input
                    id="email-ro"
                    value={profile?.email ?? ""}
                    readOnly
                    className="mt-1 w-full cursor-not-allowed rounded-lg border border-stone-100 bg-stone-50 px-3 py-2 text-stone-600"
                  />
                </div>
                <div>
                  <label htmlFor="phone" className="block text-sm font-medium text-stone-700">
                    {t("common.phone")}
                  </label>
                  <input
                    id="phone"
                    value={phone}
                    onChange={(e) => setPhone(e.target.value)}
                    type="tel"
                    className="mt-1 w-full rounded-lg border border-stone-200 px-3 py-2"
                  />
                </div>
              </div>
            </section>

            <section className="rounded-2xl border border-stone-200 bg-white p-5 shadow-sm">
              <h2 className="font-display text-lg font-semibold text-stone-900">
                {t("account.shippingTitle")}
              </h2>
              <div className="mt-4 grid gap-4">
                <div>
                  <label htmlFor="recipient" className="block text-sm font-medium text-stone-700">
                    {t("account.recipient")}
                  </label>
                  <input
                    id="recipient"
                    value={shippingRecipient}
                    onChange={(e) => setShippingRecipient(e.target.value)}
                    className="mt-1 w-full rounded-lg border border-stone-200 px-3 py-2"
                  />
                </div>
                <div>
                  <label htmlFor="line1" className="block text-sm font-medium text-stone-700">
                    {t("account.line1")}
                  </label>
                  <input
                    id="line1"
                    value={line1}
                    onChange={(e) => setLine1(e.target.value)}
                    className="mt-1 w-full rounded-lg border border-stone-200 px-3 py-2"
                  />
                </div>
                <div>
                  <label htmlFor="line2" className="block text-sm font-medium text-stone-700">
                    {t("account.line2")}
                  </label>
                  <input
                    id="line2"
                    value={line2}
                    onChange={(e) => setLine2(e.target.value)}
                    className="mt-1 w-full rounded-lg border border-stone-200 px-3 py-2"
                  />
                </div>
                <div className="grid gap-4 sm:grid-cols-2">
                  <div>
                    <label htmlFor="city" className="block text-sm font-medium text-stone-700">
                      {t("account.city")}
                    </label>
                    <input
                      id="city"
                      value={city}
                      onChange={(e) => setCity(e.target.value)}
                      className="mt-1 w-full rounded-lg border border-stone-200 px-3 py-2"
                    />
                  </div>
                  <div>
                    <label htmlFor="state" className="block text-sm font-medium text-stone-700">
                      {t("account.state")}
                    </label>
                    <input
                      id="state"
                      value={state}
                      onChange={(e) => setState(e.target.value)}
                      className="mt-1 w-full rounded-lg border border-stone-200 px-3 py-2"
                    />
                  </div>
                  <div>
                    <label htmlFor="postcode" className="block text-sm font-medium text-stone-700">
                      {t("account.postcode")}
                    </label>
                    <input
                      id="postcode"
                      value={postcode}
                      onChange={(e) => setPostcode(e.target.value)}
                      className="mt-1 w-full rounded-lg border border-stone-200 px-3 py-2"
                    />
                  </div>
                  <div>
                    <label htmlFor="country" className="block text-sm font-medium text-stone-700">
                      {t("account.country")}
                    </label>
                    <input
                      id="country"
                      value={country}
                      onChange={(e) => setCountry(e.target.value)}
                      className="mt-1 w-full rounded-lg border border-stone-200 px-3 py-2"
                    />
                  </div>
                </div>
              </div>
            </section>

            {error ? (
              <p className="text-sm text-red-800" role="alert">
                {error}
              </p>
            ) : null}
            {ok ? (
              <p className="text-sm text-green-800">{t("account.profileSaved")}</p>
            ) : null}

            <div className="flex flex-wrap gap-3">
              <button
                type="submit"
                disabled={saving}
                className="rounded-lg bg-amber-800 px-5 py-2.5 text-sm font-medium text-white hover:bg-amber-900 disabled:opacity-50"
              >
                {saving ? t("account.saving") : t("account.saveChanges")}
              </button>
              <Link
                href="/"
                className="rounded-lg border border-stone-300 px-5 py-2.5 text-sm font-medium text-stone-800 hover:bg-stone-50"
              >
                {t("account.backToShop")}
              </Link>
              <button
                type="button"
                onClick={() => void logout()}
                className="ml-auto text-sm font-medium text-red-800 hover:underline"
              >
                {t("account.logOut")}
              </button>
            </div>
            </form>
          </div>
        )}
      </main>
    </div>
  );
}
