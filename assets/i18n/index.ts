import { createI18n } from 'vue-i18n';

export type AppLocale = 'fr' | 'en';

export const SUPPORTED_LOCALES: AppLocale[] = ['fr', 'en'];
export const DEFAULT_LOCALE: AppLocale = 'fr';

const STORAGE_KEY = 'mriviere.locale';

function detectInitialLocale(): AppLocale {
  if (typeof window === 'undefined') return DEFAULT_LOCALE;

  // Only the homepage keeps explicit /fr and /en URLs (for SEO / sitemap).
  // Hitting them forces the saved locale.
  const path = window.location.pathname;
  if (path === '/fr') return 'fr';
  if (path === '/en') return 'en';

  // Stripe redirects the customer to a fresh tab; respect ?locale=fr|en if present
  // so the confirmation/cancel page renders in the language used at checkout.
  const queryLocale = new URLSearchParams(window.location.search).get('locale');
  if (queryLocale === 'fr' || queryLocale === 'en') {
    try { localStorage.setItem(STORAGE_KEY, queryLocale); } catch { /* ignore */ }
    return queryLocale;
  }

  try {
    const stored = localStorage.getItem(STORAGE_KEY);
    if (stored === 'fr' || stored === 'en') return stored;
  } catch {
    /* localStorage unavailable */
  }

  if (navigator.language.toLowerCase().startsWith('en')) return 'en';
  return DEFAULT_LOCALE;
}

// Locales are loaded lazily so the inactive one stays out of the main bundle.
// The active locale is pulled in synchronously below — tiny enough to avoid a
// flash of untranslated content, while the other one is fetched on demand by
// `setLocale` (e.g. when the user toggles the language switcher).
const loaders = {
  fr: () => import('@/locales/fr.json'),
  en: () => import('@/locales/en.json'),
} as const;

const loadedLocales: Record<AppLocale, boolean> = { fr: false, en: false };

const initialLocale = detectInitialLocale();
const initialMessages = (await loaders[initialLocale]()).default;
loadedLocales[initialLocale] = true;

// Both keys must exist at construction so vue-i18n infers a stable
// union for `i18n.global.locale`. The inactive locale starts as `{}`
// and is hydrated by `setLocale` on demand.
const bootMessages = {
  fr: initialLocale === 'fr' ? initialMessages : {},
  en: initialLocale === 'en' ? initialMessages : {},
};

export const i18n = createI18n({
  legacy: false,
  locale: initialLocale,
  fallbackLocale: DEFAULT_LOCALE,
  messages: bootMessages,
  numberFormats: {
    fr: {
      currency: { style: 'currency', currency: 'CAD', currencyDisplay: 'narrowSymbol', maximumFractionDigits: 0 },
      currencyCents: { style: 'currency', currency: 'CAD', currencyDisplay: 'narrowSymbol', minimumFractionDigits: 2 },
    },
    en: {
      currency: { style: 'currency', currency: 'CAD', currencyDisplay: 'narrowSymbol', maximumFractionDigits: 0 },
      currencyCents: { style: 'currency', currency: 'CAD', currencyDisplay: 'narrowSymbol', minimumFractionDigits: 2 },
    },
  },
  datetimeFormats: {
    fr: {
      short: { year: 'numeric', month: '2-digit', day: '2-digit' },
      long: { year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit' },
    },
    en: {
      short: { year: 'numeric', month: '2-digit', day: '2-digit' },
      long: { year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit' },
    },
  },
});

export async function setLocale(locale: AppLocale): Promise<void> {
  if (!loadedLocales[locale]) {
    const messages = await loaders[locale]();
    i18n.global.setLocaleMessage(locale, messages.default);
    loadedLocales[locale] = true;
  }
  i18n.global.locale.value = locale;
  document.documentElement.lang = locale;
  try {
    localStorage.setItem(STORAGE_KEY, locale);
  } catch {
    /* localStorage unavailable */
  }
}

export function formatMoney(cents: number, locale?: AppLocale): string {
  const loc = locale ?? (i18n.global.locale.value as AppLocale);
  return new Intl.NumberFormat(loc === 'fr' ? 'fr-CA' : 'en-CA', {
    style: 'currency',
    currency: 'CAD',
    currencyDisplay: 'narrowSymbol',
    maximumFractionDigits: 0,
  }).format(cents / 100);
}
