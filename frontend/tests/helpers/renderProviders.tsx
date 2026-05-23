import type { ReactNode } from "react";
import { render } from "@testing-library/react";
import { CurrencyProvider } from "@/contexts/currency-context";
import { LocaleProvider } from "@/contexts/locale-context";

export function renderWithStorefrontProviders(ui: ReactNode) {
  return render(
    <LocaleProvider>
      <CurrencyProvider>{ui}</CurrencyProvider>
    </LocaleProvider>,
  );
}
