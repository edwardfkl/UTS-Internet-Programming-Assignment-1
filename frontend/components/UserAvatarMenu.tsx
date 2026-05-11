"use client";

import Image from "next/image";
import Link from "next/link";
import { useCallback, useEffect, useId, useRef, useState } from "react";
import { adminPanelUrl } from "@/lib/api";
import { syncAdminWebSession } from "@/lib/adminSessionApi";
import { useLocale } from "@/contexts/locale-context";
import type { AuthUser } from "@/lib/types";

function PersonIcon({ className }: { className?: string }) {
  return (
    <svg
      className={className}
      viewBox="0 0 24 24"
      fill="none"
      stroke="currentColor"
      strokeWidth={1.5}
      strokeLinecap="round"
      strokeLinejoin="round"
      aria-hidden
    >
      <path d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0z" />
      <path d="M4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
    </svg>
  );
}

type UserAvatarMenuProps = {
  user: AuthUser;
  onLogout: () => void;
};

export function UserAvatarMenu({ user, onLogout }: UserAvatarMenuProps) {
  const { t, tf } = useLocale();
  const [open, setOpen] = useState(false);
  const [imgBroken, setImgBroken] = useState(false);
  const rootRef = useRef<HTMLDivElement>(null);
  const adminOpenBusyRef = useRef(false);
  const menuId = useId();

  const close = useCallback(() => setOpen(false), []);

  const rawUrl = user.avatar_url?.trim() ?? "";
  const showImg = rawUrl.length > 0 && !imgBroken;

  useEffect(() => {
    setImgBroken(false);
  }, [user.avatar_url]);

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
        className={`flex h-9 w-9 shrink-0 items-center justify-center rounded-full border border-stone-200 shadow-sm ring-offset-2 outline-none focus-visible:ring-2 focus-visible:ring-amber-700 ${
          showImg
            ? "overflow-hidden bg-stone-100 p-0 ring-offset-white"
            : "bg-gradient-to-br from-amber-100 to-amber-200 text-amber-950 hover:from-amber-200 hover:to-amber-300"
        }`}
        aria-expanded={open}
        aria-haspopup="menu"
        aria-controls={open ? menuId : undefined}
        onClick={() => setOpen((v) => !v)}
        title={user.email}
      >
        <span className="sr-only">{tf("avatar.openMenu", { name: user.name })}</span>
        {showImg ? (
          <Image
            src={rawUrl}
            alt=""
            width={36}
            height={36}
            className="h-full w-full object-cover"
            unoptimized
            onError={() => setImgBroken(true)}
            referrerPolicy="no-referrer"
          />
        ) : (
          <PersonIcon className="h-5 w-5" />
        )}
      </button>

      {open ? (
        <div
          id={menuId}
          role="menu"
          aria-orientation="vertical"
          className="absolute right-0 mt-2 min-w-[11rem] rounded-xl border border-stone-200 bg-white py-1 shadow-lg"
        >
          <Link
            href="/account"
            role="menuitem"
            className="block px-4 py-2.5 text-sm text-stone-800 hover:bg-stone-50"
            onClick={close}
          >
            {t("avatar.myAccount")}
          </Link>
          <Link
            href="/account/orders"
            role="menuitem"
            className="block px-4 py-2.5 text-sm text-stone-800 hover:bg-stone-50"
            onClick={close}
          >
            {t("avatar.orderHistory")}
          </Link>
          <Link
            href="/account#change-password"
            role="menuitem"
            className="block px-4 py-2.5 text-sm text-stone-800 hover:bg-stone-50"
            onClick={close}
          >
            {t("avatar.changePassword")}
          </Link>
          {user.is_admin ? (
            <button
              type="button"
              role="menuitem"
              className="block w-full px-4 py-2.5 text-left text-sm text-stone-800 hover:bg-stone-50"
              onClick={(e) => {
                e.preventDefault();
                e.stopPropagation();
                if (adminOpenBusyRef.current) return;
                adminOpenBusyRef.current = true;
                close();
                void (async () => {
                  try {
                    try {
                      await syncAdminWebSession();
                    } catch {
                      /* session cookie may be missing; /admin/login still works */
                    }
                    window.open(adminPanelUrl(), "_blank", "noopener,noreferrer");
                  } finally {
                    window.setTimeout(() => {
                      adminOpenBusyRef.current = false;
                    }, 800);
                  }
                })();
              }}
            >
              {t("avatar.adminPanel")}
            </button>
          ) : null}
          <hr className="my-1 border-stone-100" />
          <button
            type="button"
            role="menuitem"
            className="w-full px-4 py-2.5 text-left text-sm font-medium text-red-800 hover:bg-red-50"
            onClick={() => {
              close();
              void onLogout();
            }}
          >
            {t("avatar.logOut")}
          </button>
        </div>
      ) : null}
    </div>
  );
}
