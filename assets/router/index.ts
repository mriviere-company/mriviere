import { nextTick } from 'vue';
import { createRouter, createWebHistory, type RouteRecordRaw } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import { i18n, setLocale, type AppLocale } from '@/i18n';

export const supportsViewTransitions =
  typeof document !== 'undefined' && typeof document.startViewTransition === 'function';

// Flat URL structure : a single canonical path per page (FR slugs).
// Only the home page exposes language-specific URLs (/fr, /en) so that
// crawlers can index both locales via sitemap.xml. Everywhere else the
// content language follows the user's stored preference (localStorage).
const PATHS = {
  home: '/',
  pricing: '/forfaits',
  package: '/forfaits',
  profile: '/profil',
  quote: '/devis',
  'quote-confirmation': '/devis/confirmation',
  contact: '/contact',
  legals: '/mentions-legales',
  terms: '/cgv',
} as const;

type PathKey = keyof typeof PATHS;

export function localePath(name: PathKey, extra: string | undefined = undefined): string {
  const base = PATHS[name];
  return extra ? `${base}/${extra}` : base;
}

export function switchLocale(targetLocale: AppLocale): void {
  void setLocale(targetLocale);
}

const publicRoutes: RouteRecordRaw[] = [
  // Home — three URLs for SEO (sitemap.xml lists all three with hreflang).
  { path: '/', name: 'home', component: () => import('@/views/HomeView.vue'), meta: { titleKey: 'nav.home' } },
  { path: '/fr', name: 'home-fr', component: () => import('@/views/HomeView.vue'), meta: { titleKey: 'nav.home', forceLocale: 'fr' } },
  { path: '/en', name: 'home-en', component: () => import('@/views/HomeView.vue'), meta: { titleKey: 'nav.home', forceLocale: 'en' } },

  { path: '/forfaits', name: 'pricing', component: () => import('@/views/PricingView.vue'), meta: { titleKey: 'nav.pricing' } },
  { path: '/forfaits/:slug', name: 'package', component: () => import('@/views/PackageDetailView.vue'), meta: { titleKey: 'package.eyebrow' } },
  { path: '/profil', name: 'profile', component: () => import('@/views/ProfileView.vue'), meta: { titleKey: 'nav.profile' } },
  { path: '/devis', name: 'quote', component: () => import('@/views/QuoteView.vue'), meta: { titleKey: 'nav.quote' } },
  { path: '/devis/confirmation/:id', name: 'quote-confirmation', component: () => import('@/views/QuoteConfirmationView.vue'), meta: { titleKey: 'quote.eyebrow' } },
  { path: '/contact', name: 'contact', component: () => import('@/views/ContactView.vue'), meta: { titleKey: 'nav.contact' } },
  { path: '/mentions-legales', name: 'legals', component: () => import('@/views/LegalsView.vue'), meta: { titleKey: 'nav.legals' } },
  { path: '/cgv', name: 'terms', component: () => import('@/views/TermsView.vue'), meta: { titleKey: 'nav.terms' } },
];

export const router = createRouter({
  history: createWebHistory(),
  routes: [
    ...publicRoutes,
    {
      path: '/admin/login',
      name: 'admin-login',
      component: () => import('@/views/admin/AdminLoginView.vue'),
    },
    {
      path: '/admin',
      component: () => import('@/views/admin/AdminShell.vue'),
      meta: { requiresAuth: true },
      children: [
        { path: '', redirect: '/admin/dashboard' },
        { path: 'dashboard', name: 'admin-dashboard', component: () => import('@/views/admin/DashboardView.vue') },
        { path: 'quotes', name: 'admin-quotes', component: () => import('@/views/admin/QuotesView.vue') },
        { path: 'quotes/:id', name: 'admin-quote', component: () => import('@/views/admin/QuoteDetailView.vue') },
        { path: 'callbacks', name: 'admin-callbacks', component: () => import('@/views/admin/CallbacksView.vue') },
        { path: 'sites', name: 'admin-sites', component: () => import('@/views/admin/SitesView.vue') },
        { path: 'sites/new', name: 'admin-sites-new', component: () => import('@/views/admin/SiteCreateView.vue') },
        { path: 'sites/:id', name: 'admin-site', component: () => import('@/views/admin/SiteDetailView.vue') },
        { path: 'sites/:id/admins', name: 'admin-site-admins', component: () => import('@/views/admin/SiteAdminsView.vue') },
        { path: 'aggregated', name: 'admin-aggregated', component: () => import('@/views/admin/AggregatedView.vue') },
        { path: 'messages', name: 'admin-messages', component: () => import('@/views/admin/MessagesView.vue') },
        { path: 'subscriptions', name: 'admin-subscriptions', component: () => import('@/views/admin/SubscriptionsView.vue') },
        { path: 'settings', name: 'admin-settings', component: () => import('@/views/admin/SettingsView.vue') },
      ],
    },
    {
      path: '/:pathMatch(.*)*',
      name: 'not-found',
      component: () => import('@/views/NotFoundView.vue'),
    },
  ],
  scrollBehavior(_to, _from, saved) {
    return saved ?? { top: 0, behavior: 'smooth' };
  },
});

router.beforeEach(async (to) => {
  // The two SEO-only home variants (/fr and /en) force their locale on landing
  // and persist it in localStorage so subsequent navigation keeps the choice.
  const forced = to.meta.forceLocale as AppLocale | undefined;
  if (forced) {
    await setLocale(forced);
  }

  if (to.meta.requiresAuth) {
    const auth = useAuthStore();
    if (!auth.user) await auth.fetchMe();
    if (!auth.user) return { name: 'admin-login', query: { redirect: to.fullPath } };
  }
  return true;
});

// View Transitions plumbing.
const VT_DEBUG = false;
const vtLog = (msg: string, extra: unknown = '') => {
  if (VT_DEBUG) console.log(`[VT] ${msg}`, extra);
};

let resolvePendingTransition: (() => void) | null = null;

router.beforeResolve((to, from) => {
  vtLog('beforeResolve fired', { from: from.fullPath, to: to.fullPath });

  if (!from.name) return true;
  if (!supportsViewTransitions) return true;
  if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return true;
  if (to.path === from.path) return true;

  return new Promise<boolean>((resolveGuard) => {
    const transition = document.startViewTransition!(() => {
      resolveGuard(true);
      return new Promise<void>((resolveCallback) => {
        resolvePendingTransition = resolveCallback;
      });
    });

    transition.ready.then(() => vtLog('ready')).catch(() => {});
    transition.finished.then(() => vtLog('finished')).catch(() => {});

    setTimeout(() => {
      if (resolvePendingTransition) {
        resolvePendingTransition();
        resolvePendingTransition = null;
      }
    }, 1500);
  });
});

router.afterEach((to) => {
  const baseTitle = 'Matthieu Rivière';
  const titleKey = to.meta.titleKey as string | undefined;
  const pageTitle = titleKey ? i18n.global.t(titleKey) : '';
  document.title = pageTitle ? `${pageTitle} — ${baseTitle}` : baseTitle;

  if (resolvePendingTransition) {
    const resolve = resolvePendingTransition;
    resolvePendingTransition = null;
    nextTick(() => resolve());
  }
});

router.onError(() => {
  if (resolvePendingTransition) {
    resolvePendingTransition();
    resolvePendingTransition = null;
  }
});
