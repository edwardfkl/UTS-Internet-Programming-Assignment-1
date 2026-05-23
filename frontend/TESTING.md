# Testing guide (Assignment storefront)

## Backend (Laravel) — API + Admin

```bash
cd backend
php artisan test
```

**162+ feature/unit tests** cover:

| Area | Examples |
|------|----------|
| Auth | register, login, logout, JWT, suspended users |
| Cart & checkout | items, stock, stale token 404, attach, promo |
| Orders & reviews | user orders, product reviews |
| Products | active/inactive, search |
| Profile & password | show, update, change password |
| Promo codes | preview, discount rules |
| Admin | users, products, orders, promo CRUD, bulk actions, filters |
| Locale API | preference cookie |

## Frontend (Next.js) — Vitest

```bash
cd frontend
npm test          # single run
npm run test:watch
```

| File | Covers |
|------|--------|
| `tests/api.cart.test.ts` | Cart token storage, stale-token recovery, checkout client |
| `tests/api.auth.test.ts` | Login, register, logout, attach cart |
| `tests/api.profile-password.test.ts` | Profile + password API |
| `tests/api.catalog-orders.test.ts` | Products, reviews, orders, promo preview |
| `tests/adminSessionApi.test.ts` | Admin web session sync |
| `tests/authToken.test.ts` | localStorage auth token |
| `tests/localeSync.test.ts` | Locale API mapping |
| `tests/i18n-resolve.test.ts` | Message key resolution |
| `tests/locale-context.test.tsx` | i18n provider |
| `tests/currencies.test.ts` | Currency config |
| `tests/currency-context.test.tsx` | Currency provider |
| `tests/money.test.ts` | Formatting & conversion |
| `tests/paymentInstructions.test.ts` | Checkout payment copy |
| `tests/auth-context.test.tsx` | Auth provider flows |
| `tests/useCart.test.tsx` | Cart hook |
| `tests/CartPanel.test.tsx` | Cart UI |
| `tests/ShopHeader.test.tsx` | Header / nav |

Admin Blade UI is exercised via **backend feature tests** (HTTP to `/admin/...`), not JSDOM.
