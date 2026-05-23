"use client";

import { AuthProvider } from "@/contexts/auth-context";
import { CurrencyProvider } from "@/contexts/currency-context";
import { LocaleProvider } from "@/contexts/locale-context";

export function Providers({ children }: { children: React.ReactNode }) {
  return (
    <LocaleProvider>
      <CurrencyProvider>
        <AuthProvider>{children}</AuthProvider>
      </CurrencyProvider>
    </LocaleProvider>
  );
}
