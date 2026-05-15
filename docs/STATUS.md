# État du projet — rivierematthieu.com / www.rivierematthieu.com

> Lis ce fichier en premier dans toute nouvelle session Claude Code. Il fait
> le point sur **ce qui est fait**, **comment c'est architecturé**, et **ce
> qu'il reste à attaquer**. Les autres docs (`DEPLOY.md`, `STRIPE.md`,
> `CENTRAL_DASHBOARD_BRIEF.md`) sont des approfondissements thématiques.

Date du snapshot : **2026-05-10**.
État : **MVP fonctionnel local** — pas encore déployé en prod.

> Refacto 2026-05-10 : front réorganisé en **Atomic Design** (`atoms/molecules/organisms`) avec design system formalisé sur les composants `Ui*`. Shell Twig SPA supprimé : la SPA est totalement autonome, le HTML d'amorçage est émis en PHP par `SpaController` via `Pentatrion\ViteBundle\Service\EntrypointRenderer`. Twig ne sert plus qu'aux templates d'emails. Les services backend explicitent les principes **SOLID**.

---

## 0. Identité du projet

- **Nom de marque** : « Studio de développement web — Matthieu Rivière »
- **Propriétaire** : Matthieu Rivière (NEQ 2279489522, Brossard QC)
- **Domaine actuel** : `rivierematthieu.com` (référence dans le code)
- **Domaine cible production** : `https://www.rivierematthieu.com` (à swap quand prêt)
- **Showcase / site d'exemple** : `https://exemple.rivierematthieu.com` (lien dans le header)
- **Public visé** : artisans, commerces et PME québécoises qui veulent un site sur-mesure
- **Modèle commercial** : 3 forfaits (STARTER 399 $ + 29 $/mois, STANDARD 599 $ + 49 $/mois, PREMIUM 899 $ + 79 $/mois). Engagement 12 mois. Acompte 50 % via Stripe. TPS/TVQ non applicable (petit fournisseur).

---

## 1. Stack & versions verrouillées

| Domaine | Version |
|---|---|
| PHP | **8.4.x** (testé 8.4.19) |
| Symfony | **8.0.x LTS** (framework-bundle 8.0.10) |
| Doctrine ORM | 3.6.x |
| MariaDB | 10.11+ |
| Vue | 3.5.x |
| Vue Router | 4.x |
| Pinia | 2.x |
| vue-i18n | **10** (Composition API mode) |
| TypeScript | 5.6 strict |
| Vite | 5.x |
| pentatrion/vite-bundle | 8.2.x |
| Stripe SDK | stripe-php ^15 |
| Tests | PHPUnit 11, Vitest 2, Playwright 1.48 |

**Hébergement cible** : Hostinger mutualisé PHP + MariaDB. SSH limité ⇒ pas de daemon long-running, pas de Symfony Messenger consumer. Les crons éventuels passent par le panel cron Hostinger + commandes Symfony.

---

## 2. Identité visuelle

### Palette (déclarée dans `assets/styles/tokens.scss`)

| Nom | Hex | Rôle light | Rôle dark |
|---|---|---|---|
| **Navy** | `#0F2A5F` | accent principal (`--color-accent`) | sub-accent |
| **Cobalt** | `#2B5BD7` | secondaire (`--color-accent-2`) | accent principal (Navy trop sombre sur Ink) |
| **Ink** | `#0A0E1A` | `--color-text` | `--color-bg` |
| **Paper** | `#FAFAF7` | `--color-bg` | `--color-text` |

Status colors (success/warn/danger) restent verts/ambre/rouges classiques, distincts de la marque.

### Typographie

- **Display** : `Bricolage Grotesque Variable` (`@fontsource-variable/bricolage-grotesque`)
- **Corps** : `Inter Variable`
- **Mono** : `ui-monospace` system stack
- Toujours via `var(--font-display)`, `var(--font-body)`, `var(--font-mono)` — jamais en dur.

### Logos

Dans `public/img/` :
- `mriviere-mark.svg` (mark sur fond clair) + `mriviere-mark-inverse.svg` (sur fond sombre)
- PNGs raster en 32/64/180/512/1080 pour favicons, Apple Touch, og:image
- `mriviere-wordmark-{size}.png` et variante `-dark` pour OG / contextes nécessitant raster

Favicons :
- `public/favicon.ico` (light theme)
- `public/favicon-dark.ico` (dark theme)
- Swap **piloté par JS** dans `assets/main.ts` via `MutationObserver` sur `[data-theme]` (le `media` query Chrome est buggé sur ICO).

### Mode sombre / clair

- **Default** : sombre (Ink). `<html data-theme="dark">` hardcodé dans `templates/base.html.twig` pour zéro flash au premier render.
- Si `localStorage['mriviere.theme']` est posé, on respecte. Sinon, `prefers-color-scheme: light` bascule en light.
- Toggle Sun/Moon dans le header met à jour `data-theme` + sauvegarde en localStorage.

---

## 3. Routing & i18n

### Bilinguisme FR/EN

- **Locale par défaut** : `fr-CA`
- **Stockage** : `localStorage['mriviere.locale']`
- **Détection** au boot (`assets/i18n/index.ts`) :
  1. Path exact `/fr` ou `/en` (force la locale + persiste)
  2. localStorage
  3. `navigator.language` commence par `en` → `'en'`
  4. Fallback `'fr'`
- **Switcher** : bouton Globe dans `SiteHeader.vue` → `setLocale(target)`. **Aucune navigation** : Vue re-rend les `t('clé')` réactivement.

### URLs publiques (FR slugs canoniques)

Les URLs des pages internes sont **uniques par page** et utilisent les slugs FR :

| Page | URL |
|---|---|
| Accueil | `/`, `/fr`, `/en` (3 variantes pour SEO uniquement, sitemap les liste avec hreflang) |
| Forfaits | `/forfaits` |
| Détail forfait | `/forfaits/:slug` |
| Profil | `/profil` |
| Devis (4 étapes + Stripe) | `/devis` |
| Confirmation devis | `/devis/confirmation/:id` |
| Contact | `/contact` |
| Mentions légales | `/mentions-legales` |
| CGV | `/cgv` |
| Sitemap | `/sitemap.xml` |
| Robots | `/robots.txt` |

L'admin reste sur `/admin/...` (FR uniquement, usage perso).

### SEO

- `public/robots.txt` : autorise tout sauf `/admin/`, `/api/`, `/stripe/`. Pointe vers `https://www.rivierematthieu.com/sitemap.xml`.
- `src/Controller/SeoController.php` : génère `/sitemap.xml` dynamiquement avec `hreflang` sur `/`, `/fr`, `/en`.
- `templates/base.html.twig` : balises Open Graph, favicons, Apple Touch, og:image (`/img/mriviere-wordmark-1080.png`).

---

## 4. Architecture backend

```
src/
├── Config/                  Enums (PackageSlug, QuoteStatus, MessageStatus)
├── Controller/              (controllers fins, logique déléguée aux services)
│   ├── Api/                 Endpoints publics (/api/packages, /api/contact, /api/quotes,
│   │                        /api/profile, /api/callbacks)
│   ├── Admin/               Endpoints back-office (/api/admin/*)
│   ├── SeoController.php    /sitemap.xml
│   ├── SpaController.php    Catch-all → émet le HTML d'amorçage en PHP
│   │                        (EntrypointRenderer + CsrfTokenManager, aucun Twig)
│   └── StripeWebhookController.php
├── DataFixtures/            AppFixtures — admin, 3 forfaits, profil, seeds dev
├── Dto/                     Objets de requête validés
├── Entity/                  AdminUser, Package, Quote, ContactMessage,
│                            ProfileContent, CallbackRequest, ManagedSite
├── Repository/              ServiceEntityRepository
├── Security/                JsonAuth handlers (success/failure)
└── Service/                 (services SOLID, 1 responsabilité chacun, autowiring par interface)
    ├── MailerService.php
    ├── StripeService.php    (currency 'cad', subscription + add_invoice_items)
    ├── RequestPayloadDeserializer.php
    └── SuperAdmin/
        ├── JwtSigner.php       (EdDSA via libsodium, 5 tests passants)
        ├── HealthResponse.php  (DTO typé)
        └── SuperAdminClient.php (HTTP wrapper /api/super-admin/*)
```

**Conventions SOLID en pratique** :

- **SRP** : chaque service du dossier `Service/` traite **un seul** sujet (envoi email, gateway Stripe, signature JWT, ping santé). On découpe avant qu'une classe atteigne ~250 lignes ou ne mélange deux sujets.
- **OCP** : ajout d'un nouveau gateway = nouveau service injectable, jamais un `switch` qui grossit.
- **LSP** : enums et value objects ne mentent pas sur leur contrat. Les implémentations respectent la signature de l'interface.
- **ISP** : on type-hint sur des interfaces étroites de Symfony (`MailerInterface`, `CsrfTokenManagerInterface`, `LoggerInterface`) plutôt que sur des classes concrètes monolithiques.
- **DIP** : autowiring constructeur partout, jamais de `new SomeService()` dans un controller. Les frontières externes (Stripe, HTTP, mail) sont systématiquement encapsulées dans un service du projet.

### Routes API

| Endpoint | Méthode | Auth | Rôle |
|---|---|---|---|
| `/api/packages` | GET | public | liste des 3 forfaits |
| `/api/contact` | POST | public + rate-limit | message contact (honeypot) |
| `/api/quotes` | POST | public + rate-limit | crée devis + redirige Stripe |
| `/api/quotes/{id}` | GET | public | confirmation devis |
| `/api/profile` | GET | public | bio + stack |
| `/api/callbacks` | POST | public + rate-limit | demande de rappel téléphonique |
| `/api/admin/login` | POST | public | json_login Symfony |
| `/api/admin/logout` | POST | session | logout |
| `/api/admin/me` | GET | public | retourne user ou 401 |
| `/api/admin/dashboard` | GET | ROLE_ADMIN | KPIs (5 stats) |
| `/api/admin/quotes` | GET | ROLE_ADMIN | liste devis |
| `/api/admin/quotes/{id}` | GET | ROLE_ADMIN | détail devis |
| `/api/admin/messages` | GET / POST `/{id}/read` | ROLE_ADMIN | messages contact |
| `/api/admin/callbacks` | GET / POST `/{id}/{read,archive}` | ROLE_ADMIN | rappels |
| `/api/admin/subscriptions` | GET | ROLE_ADMIN | abonnements Stripe (read-only) |
| `/api/admin/profile` | PUT | ROLE_ADMIN | éditer bio + stack |
| `/api/admin/sites` | GET / POST / PATCH / DELETE / POST `/{id}/check-health` | ROLE_ADMIN | central dashboard sites clients |
| `/stripe/webhook` | POST | signature | events Stripe |

### Modèle de données (7 entités)

```
AdminUser            email unique, password bcrypt, roles ['ROLE_ADMIN'], lastLoginAt
Package              slug enum, prix CAD cents, features json, stripeMonthlyPriceId
Quote                UUID v7, FK package, infos client, options, totaux, status enum,
                     stripe* IDs, ip, timestamps
ContactMessage       name, email, message, status enum (unread/read/archived), ip
ProfileContent       singleton id=1, bio, stack json, links json
CallbackRequest      name, email (optionnel), phone, preferredSlot, message, status, ip
ManagedSite         (central dashboard) domain, label, publicKeyFingerprint,
                     enabled, addedAt, lastSeenAt, lastHealthStatus
```

3 migrations :
- `Version20260509000001` — schéma initial (5 tables)
- `Version20260509000002` — `callback_requests`
- `Version20260509000003` — `managed_sites`

---

## 5. Architecture frontend

SPA Vue 3 **totalement autonome** — aucun rendu Twig dans le chemin client. Le shell HTML est émis en PHP par `SpaController` (lit le manifest Vite via `EntrypointRenderer` et injecte le token CSRF via `CsrfTokenManager`).

L'arborescence des composants suit **Atomic Design** + un **design system** maison (composants `Ui*` qui consomment exclusivement les tokens de `assets/styles/tokens.scss`) :

| Niveau | Rôle | Composants |
|---|---|---|
| `atoms/` | primitives sans état métier | `UiButton`, `UiField`, `UiTag` |
| `molecules/` | combinaisons d'atoms | `UiCard` |
| `organisms/` | blocs autonomes, souvent stateful | `SiteHeader`, `SiteFooter`, `CallbackBookingModal` |
| `views/` | pages assemblant atoms + molecules + organisms | `HomeView`, `QuoteView`, `admin/*` |

```
assets/
├── api/                Client axios (CSRF, intercepteurs)
├── components/         Atomic Design + design system
│   ├── atoms/          UiButton, UiField, UiTag (+ __tests__/)
│   ├── molecules/      UiCard
│   └── organisms/      SiteHeader (switcher locale Globe), SiteFooter, CallbackBookingModal
├── composables/        useTheme
├── i18n/               vue-i18n setup + formatMoney CAD + helpers
├── locales/            fr.json, en.json — toutes les chaînes UI
├── router/             Routes flat (FR slugs canoniques) + helpers localePath/switchLocale
├── stores/             Pinia (packages, auth, quote)
├── styles/             tokens.scss, reset.scss, utilities.scss, transitions.scss,
│                       home-snap.scss, index.scss
├── types/              TS types partagés
├── views/              Vues publiques + admin/
├── App.vue             Layout racine (toggle home-snap, fallback Vue Transition)
└── main.ts             Entry Vite (Pinia + router + i18n + favicon swap)
```

### Vues publiques

- `HomeView.vue` — Hero (orbes Navy/Cobalt), Features (4), Pricing preview (3 cartes), CTA-block (3 cartes : Courriel, Réserver appel, Commander)
- `PricingView.vue` — Tableau comparatif + FAQ accordéon
- `PackageDetailView.vue` — Détail d'un forfait
- `ProfileView.vue` — Bio (Vidéotron, TVA Nouvelles, Marie Claire…) + stack
- `QuoteView.vue` — Formulaire 4 étapes, skip step 1 si `?pkg=…` posé, callback "Modifier" pour revenir
- `QuoteConfirmationView.vue` — Page de retour Stripe
- `ContactView.vue` — Formulaire libre + coordonnées (email, téléphone, adresse, NEQ)
- `LegalsView.vue` — Mentions légales (Loi 25 québécoise)
- `TermsView.vue` — CGV (droit civil québécois)
- `NotFoundView.vue` — 404

### Vues admin (FR uniquement)

- `AdminLoginView.vue` — login JSON
- `AdminShell.vue` — layout avec sidebar (7 entrées + lien « Voir le site » + logout)
- `DashboardView.vue` — 5 stats + 3 panneaux récents (callbacks, devis, messages)
- `QuotesView.vue` + `QuoteDetailView.vue`
- `MessagesView.vue` — liste messages contact
- `CallbacksView.vue` — liste demandes de rappel (filtre par statut, mark called/archive)
- `SubscriptionsView.vue` — abonnements Stripe (lecture seule)
- `SettingsView.vue` — édition bio + stack
- `SitesView.vue`, `SiteCreateView.vue`, `SiteDetailView.vue` — central dashboard (gestion clones clients)

### Effets visuels

| Effet | Fichier | Note |
|---|---|---|
| Slab reveal entre pages | `assets/styles/transitions.scss` | View Transitions API (Chrome 115+), fallback Vue `<Transition>` pour autres |
| Scroll-snap full-viewport sur home | `assets/styles/home-snap.scss` | `scroll-snap-type: y mandatory`, désactivé < 768 px |
| Reveal du contenu au scroll | `IntersectionObserver` dans `HomeView.vue` | Stagger via `--reveal-delay` CSS variable |
| Mobile menu opaque | `SiteHeader.vue` | `backdrop-filter` posé sur `::before` pour ne pas créer de containing block |
| Favicon dynamique | `main.ts` + `MutationObserver` | Swap selon `data-theme` car media query buggé sur ICO |

---

## 6. Stripe — modèle de paiement

Compte Stripe **Canada**, devise **CAD**. 3 Prices récurrents pré-créés (déjà fait par Matthieu) :

| Forfait | Variable env | Montant |
|---|---|---|
| STARTER | `STRIPE_PRICE_STARTER` | 29 $/mois |
| STANDARD | `STRIPE_PRICE_STANDARD` | 49 $/mois |
| PREMIUM | `STRIPE_PRICE_PREMIUM` | 79 $/mois |

**Pattern utilisé** : Stripe Checkout en mode `subscription` (un seul Price récurrent par session). L'acompte 50 % est ajouté à la première facture comme `InvoiceItem` côté webhook (`StripeService::addDepositInvoiceItem`, currency `'cad'`). Référence Stripe : « Subscriptions with one-time setup fees ».

Webhook : `/stripe/webhook`, événements `checkout.session.completed`, `customer.subscription.created`, `invoice.payment_failed`. Validation signature obligatoire (`Webhook::constructEvent`).

`success_url` : `${PUBLIC_BASE_URL}/devis/confirmation/{id}` (URL canonique, plus de `/fr/`).

Détails complets dans **`docs/STRIPE.md`**.

---

## 7. Central dashboard (super-admin)

Voir `docs/CENTRAL_DASHBOARD_BRIEF.md` pour le brief complet.

**Sprint 1 LIVRÉ** :
- Paire Ed25519 (`var/keys/super_admin_{private,public}.pem`, `var/` gitignored, chmod 600)
- `JwtSigner` (signature EdDSA via libsodium, 5 tests passants)
- `SuperAdminClient` (HTTP wrapper `/api/super-admin/health`, mappe codes HTTP en statuts opérationnels)
- Entité `ManagedSite` (registry des clones)
- 3 pages admin (`SitesView`, `SiteCreateView`, `SiteDetailView`)
- Endpoint `POST /api/admin/sites/{id}/check-health` (live)

**Sprint 2+ À FAIRE** : voir §10 Sprint 2 dans le brief — entités historiques `SiteHealthCheck` et `SiteDailyStats`, services `HealthMonitor` (cron 5 min) et `StatsSyncService` (cron quotidien), graphiques timeseries, CRUD admins remote (`/admin/sites/{id}/admins`), page `/admin/aggregated`, alertes email après 3 KO consécutifs.

---

## 8. Tests

### PHPUnit (14/14 verts, 36 assertions)

```
tests/Unit/
├── Dto/
│   └── ContactRequestTest.php       (3 tests)
├── Entity/
│   ├── PackageTest.php               (3 tests)
│   └── QuoteTest.php                 (3 tests)
└── Service/SuperAdmin/
    └── JwtSignerTest.php             (5 tests : self-verify, claims, jti unique,
                                       fingerprint, error handling)
```

### Vitest (9/9 verts)

```
assets/
├── components/ui/__tests__/UiButton.test.ts
└── stores/__tests__/quote.test.ts
```

### Playwright (skeletons, demandent un serveur live)

```
tests/e2e/
├── contact.spec.ts
├── pricing.spec.ts
└── admin.spec.ts
```

`composer audit` : aucune vulnérabilité.

---

## 9. Outillage dev

### Démarrage local

```bash
./start-local.sh                 # build prod + migrations + serve
./start-local.sh --reset-db      # purge + recharge fixtures
./start-local.sh --port=8765     # port custom
```

Détail dans le script lui-même (commenté). En coulisse : composer install si manquant, npm ci si manquant, `doctrine:migrations:migrate`, optionnel `fixtures:load`, `npm run build`, `cache:clear --env=prod`, puis `symfony server:start` en `APP_ENV=prod`.

### Variables d'environnement (`.env.local`)

Voir `.env` committed pour le squelette + `.env.local` (gitignored) pour les secrets.

Variables critiques :
- `DATABASE_URL` — MariaDB locale ou Hostinger
- `APP_SECRET` — `openssl rand -base64 32`
- `MAILER_DSN` — `null://null` en dev, `smtp://…` Hostinger en prod
- `PUBLIC_BASE_URL` — utilisée dans les URLs Stripe et le sitemap
- `STRIPE_*` — clés et IDs des Prices
- `SUPER_ADMIN_ISSUER` — issuer du JWT EdDSA (URL central)
- `SUPER_ADMIN_PRIVATE_KEY` — chemin vers la clé privée Ed25519
- `FROM_EMAIL`, `ADMIN_EMAIL`, `STRIPE_DASHBOARD_BASE`, `LOCK_DSN`

### Scripts npm

- `npm run dev` — Vite hot reload (port 5173)
- `npm run build` — bundle prod dans `public/build/` (~285 KB → ~106 KB gzippé)
- `npm test` — Vitest one-shot
- `npm run test:watch` — Vitest watch
- `npm run type-check` — vue-tsc

### Console Symfony fréquente

```bash
php bin/console cache:clear [--env=prod]
php bin/console debug:router
php bin/console doctrine:migrations:migrate --no-interaction
php bin/console doctrine:fixtures:load --no-interaction      # purge + recharge
```

---

## 10. Ce qui est FAIT

### ✅ Foundation
- [x] Reset du legacy PHP 2019
- [x] Symfony 7.2 → 7.3 → **8.0** + PHP 8.4
- [x] Vue 3.5 + Vite + Pinia + TS strict
- [x] vue-i18n 10 + locales FR/EN complètes (parité)
- [x] Doctrine + 3 migrations + fixtures
- [x] Auth admin Symfony Security (json_login, ROLE_ADMIN)
- [x] Rate limiter (contact 5/h, quote 3/h, callback 3/h)
- [x] Anti-spam : honeypot sur tous les formulaires publics
- [x] Stripe service (subscription + add_invoice_items, CAD)
- [x] Webhook Stripe avec validation signature
- [x] Mailer service (3 use cases : contact, quote, callback) + 6 templates Twig

### ✅ Identité visuelle
- [x] Palette Navy / Cobalt / Ink / Paper
- [x] Bricolage Grotesque + Inter
- [x] Mark + Wordmark SVG + PNG (32/64/180/512/1080)
- [x] Favicons light + dark + swap JS
- [x] Mode sombre par défaut + toggle + persistance

### ✅ Pages publiques (FR + EN)
- [x] Home avec hero animé, 3 sections snap, CTA-block 3 cartes
- [x] Forfaits + FAQ
- [x] Profil avec parcours (Marie Claire, Vidéotron, TVA Nouvelles, Groupe Investiir, Groupe Alesco, École 42)
- [x] Devis 4 étapes (skip step 1 si `?pkg=`)
- [x] Confirmation devis
- [x] Contact (formulaire + adresse + NEQ + téléphone)
- [x] Mentions légales (Loi 25)
- [x] CGV (droit québécois)
- [x] 404
- [x] Modal de réservation d'appel (datetime-local + email optionnel + persistance + email)

### ✅ Effets visuels
- [x] View Transitions API (slab reveal Navy/Cobalt)
- [x] Scroll-snap full-viewport sur home (≥ 768 px)
- [x] IntersectionObserver reveal staggered
- [x] Mobile menu opaque (backdrop-filter sur ::before)

### ✅ Admin (FR)
- [x] Dashboard 5 stats + 3 panneaux récents
- [x] Devis (liste + détail)
- [x] Messages (liste + mark read)
- [x] Rappels (liste + filtre + mark called/archive)
- [x] Abonnements (lecture seule via API Stripe)
- [x] Sites clients (Sprint 1 du central dashboard)
- [x] Profil (édition bio + stack)
- [x] Lien « Voir le site » dans la sidebar

### ✅ Central super-admin (Sprint 1)
- [x] Paire Ed25519 générée + gitignored
- [x] `JwtSigner` (libsodium) + 5 tests
- [x] `SuperAdminClient` (HTTP + types)
- [x] Entité `ManagedSite` + migration + repository
- [x] CRUD `/api/admin/sites` + endpoint check-health
- [x] 3 pages admin (liste, création, détail)

### ✅ URL & SEO
- [x] Routes flat avec FR slugs canoniques
- [x] `/`, `/fr`, `/en` pour la home (3 variantes SEO)
- [x] Locale en localStorage avec fallback `prefers-color-scheme`
- [x] Sitemap.xml dynamique avec hreflang
- [x] robots.txt
- [x] OG / Apple Touch / favicons multi-format

### ✅ Tooling
- [x] `start-local.sh`
- [x] 14 PHPUnit + 9 Vitest verts
- [x] composer audit clean
- [x] CLAUDE.md + README.md à jour

### ✅ Refacto archi (2026-05-10)
- [x] Composants front réorganisés en Atomic Design (`atoms/`, `molecules/`, `organisms/`)
- [x] Design system formalisé (composants `Ui*` consommant exclusivement les tokens SCSS)
- [x] Suppression du shell Twig SPA (`templates/base.html.twig` retiré). `SpaController` rend désormais le HTML d'amorçage en PHP via `EntrypointRenderer` + `CsrfTokenManager`. Twig restreint à `templates/emails/`.
- [x] Conventions SOLID documentées explicitement (services à responsabilité unique, autowiring par interface, controllers fins)

### ✅ Polish + Sprint 2 complet + UX produit (2026-05-10)

- [x] **Photo de profil** : entité `ProfileContent.photoUrl` (migration `Version20260510190000`), champ admin dans `SettingsView`, fallback **monogramme SVG** (`UiMonogram` atom) avec dégradé brand quand le champ est vide.
- [x] **OG image dynamique** : controller `OgImageController` (PHP GD) — endpoint `/og.png?title=…&subtitle=…&kind=…` rend une carte 1200×630 avec dégradé Navy→Cobalt + hexagone + texte. `SpaController` injecte un `og:image` différent par route (`/forfaits`, `/profil`, `/devis`, `/contact`, `/cgv`, `/mentions-legales`, home). Cache 24h.
- [x] **Cron Hostinger documenté** : `docs/DEPLOY.md` §10 décrit la config hPanel pour `app:health:check-all` (5 min) + `app:stats:sync-yesterday` (04h00). Chemin réel + binaire PHP 8.4 + redirection log.
- [x] **`SiteDailyStats`** : entité + repo + migration `Version20260510200000`, contrainte unique `(site, day)` pour upsert.
- [x] **`SuperAdminClient`** étendu : `statsSummary()`, `listAdmins()`, `createRemoteAdmin()`, `setRemoteAdminEnabled()`. DTOs typés (`StatsSummary`, `RemoteAdmin`).
- [x] **`StatsSyncService` + `app:stats:sync-yesterday`** : pull quotidien des stats de la veille, upsert dans `site_daily_stats`. Logger les sites qui échouent, ne jamais throw.
- [x] **Endpoint stats par site** : `GET /api/admin/sites/{id}/stats` (totaux + timeseries 30j + top 10 paths). Composant `UiSparkline` (molecule, SVG hand-rolled, 0 dépendance) intégré dans `SiteDetailView` avec panneau « Trafic 30 jours ».
- [x] **Page `/admin/aggregated`** : controller `AggregatedDashboardController` + `AggregatedView` — KPIs (sites, page views, visiteurs uniques), sparkline globale, top 20 paths, table par site. Lien dans la sidebar admin.
- [x] **CRUD admins distants** : endpoints `GET/POST /api/admin/sites/{id}/admins`, `POST .../{adminId}/{enable|disable}`. Vue `SiteAdminsView` avec encart « mot de passe temporaire — affiché une fois » + bouton copier. Lien depuis `SiteDetailView`.

### ✅ Polish + Sprint 2 partiel (2026-05-10)
- [x] Migration domaine `mriviere.eu` → `rivierematthieu.com` (sources, locales, emails, .env, README, CLAUDE.md, robots.txt). Seul reliquat volontaire : la description historique de `Version20260509000001`.
- [x] 9 clés i18n orphelines supprimées (`common.submit/save/saved/cancel/close/required/optional/errorGeneric/spamDetected`).
- [x] Playwright E2E activé : 9 specs vertes (`admin`, `contact`, `pricing`). `playwright.config.ts` ajoute un `globalSetup` qui purge le rate limiter avant la suite. Tests adaptés aux URLs canoniques FR.
- [x] Email templates EN : `quote_client_en.{html,txt}.twig`. Colonne `locale` ajoutée à `Quote` (migration `Version20260510170035`), DTO + controller + store Pinia transmettent la locale courante. `MailerService` choisit le template/sujet selon `quote.locale`.
- [x] 13 tests PHPUnit pour les controllers admin (`tests/Integration/Admin/AuthGuardTest.php`) — guard d'auth sur tous les endpoints protégés. Configuration test pointe sur la même DB que dev (CI/serveur partagé sans permission `CREATE DATABASE`).
- [x] **Sprint 2 central dashboard (foundation)** : entité `SiteHealthCheck` + repo, migration `Version20260510180000`, service `HealthMonitor` (cron 5 min), commande `app:health:check-all`, endpoint `GET /api/admin/sites/{id}/health-history`, panneau historique dans `SiteDetailView`. Alerte e-mail automatique au 3e échec consécutif.
- [x] Bundle code-splitting : `manualChunks` dans Vite (`vue-runtime`, `vue-i18n`, `motion`, `icons`). Chunk `app.js` réduit de **285 KB → 86 KB** (105 KB → 33 KB gzippé), reste cache-stable entre déploiements grâce à la séparation vendor.

### ✅ Polish industrialisation (2026-05-10)
- [x] **CI GitHub Actions** (`.github/workflows/ci.yml`) — PHP 8.4 + Node 22, MariaDB 10.11 service, exécute `composer audit` + migrations + fixtures + PHPUnit + vue-tsc + Vitest + build + Playwright (avec Symfony CLI installé). Upload des traces Playwright en artefact si échec.
- [x] **Stripe success_url + locale** — `success_url` et `cancel_url` embarquent `?locale={fr|en}`. `i18n/index.ts` lit ce param avant `localStorage` au boot, persiste, garantit la bonne langue après retour Stripe (qui ouvre dans un onglet vierge).
- [x] **Lazy-load locales vue-i18n** — top-level `await import()` dans `assets/i18n/index.ts`, `setLocale()` async qui hydrate l'autre locale on-demand. `vite.config.ts` cible `es2022`. **`app.js` 86 KB → 62 KB** (33 → 23 KB gzippé), `fr.js` et `en.js` deviennent des chunks séparés (~13 KB chacun).
- [x] **Tests admin happy-path** — `dama/doctrine-test-bundle` installé + activé via PHPUnit extension (rollback transactionnel automatique). `AdminWebTestCase` (login auto), `HappyPathTest` (8 tests : dashboard counters, quotes, messages mark-read, callbacks archive, profile roundtrip, ManagedSite CRUD, history+stats vides, aggregated). **PHPUnit total : 35/35**.
- [x] **Audit icônes Lucide** — script de vérif passé : aucun import orphelin sur les 24 fichiers `.vue` qui importent `lucide-vue-next`.
- [x] **Endpoint upload de fichiers** — `POST /api/admin/upload` (kind=profile, image jpg/png/webp, ≤ 2 MB, slug + suffixe random), stockage `public/uploads/profile/`. `SettingsView` enrichi : file picker + aperçu + bouton « Retirer ». `public/uploads/.gitignore` exclut le contenu user.
- [x] **Guide « ajouter une page publique »** (`docs/ADD_PUBLIC_PAGE.md`) — checklist 8 étapes (URL FR canonique, vue, route, i18n parité, header/footer, OG meta, sitemap, tests) + anti-checklist des erreurs typiques.

### ✅ Déploiement automatisé Hostinger sans Docker (2026-05-10)
- [x] **`deploy.sh`** — script local qui build + `composer install --no-dev` + `composer audit` + `rsync -avz --delete` (excludes appropriés) + commandes SSH (génération clés Ed25519 si absentes, `cache:clear`, `migrations:migrate`, append `var/log/releases.log`). Flags `--dry-run`, `--no-build`, `--skip-migrations`, `--release-tag=…`.
- [x] **`.deploy.env.example`** — gabarit credentials Hostinger (SSH user, host, port `65002`, chemin distant, binaire PHP). `.deploy.env` réel gitignored.
- [x] **`.github/workflows/deploy.yml`** — workflow **manual-trigger** (`workflow_dispatch`). Build identique au CI puis exécute `deploy.sh` avec les secrets, finit par smoke-test `curl /` + `/sitemap.xml` + `/og.png`. Inputs : `release_tag`, `skip_migrations`.
- [x] `docs/DEPLOY.md` §15 — section dédiée qui explique les deux modes, les secrets GitHub à configurer, et pourquoi pas Docker (Hostinger mutualisé n'expose pas de runtime container).

---

## 11. Ce qui RESTE à faire

### 🟢 Haute priorité (avant prod)

1. **Première mise en ligne Hostinger** — suivre `docs/DEPLOY.md` §1 à §12 manuellement (création BD, `.env.local`, `.htaccess`, SSL, premier `doctrine:fixtures:load`). Une fois fait, les déploiements suivants passent par `./deploy.sh` ou la GitHub Action **§15**. **Pas encore testé en condition réelle** — c'est le dernier verrou.

2. **Activer le cron Hostinger** — une fois déployé, configurer les deux jobs documentés dans `docs/DEPLOY.md` §10 (`app:health:check-all` toutes les 5 min, `app:stats:sync-yesterday` à 04h00).

3. **Vraie photo de profil** — infra prête (upload via `/admin/settings` → file picker, ou collage manuel d'un chemin/URL ; fallback monogramme SVG si vide). Il suffit d'uploader un JPG.

4. **Configurer la CI GitHub Actions** — workflow `.github/workflows/ci.yml` prêt. Activer Actions sur le repo et créer la branche/PR de test pour vérifier que la suite passe en environnement CI réel.

### 🟡 Sprint 2 — entièrement livré côté central, reste l'intégration côté clones

Le central dashboard est complet : `SiteHealthCheck`, `SiteDailyStats`, `HealthMonitor`, `StatsSyncService`, deux commandes cron, endpoints `/api/admin/sites/{id}/stats` + `/admin/aggregated` + CRUD admins distants, graphes timeseries, panneau historique, alertes e-mail.

Ce qui dépend désormais des **clones clients** (pas du central) :

1. Implémenter côté clone l'endpoint `GET /api/super-admin/stats/summary?day=YYYY-MM-DD` qui renvoie `{day, page_views, unique_visitors, top_paths: {path: count}}`. Tant qu'aucun clone ne l'expose, `app:stats:sync-yesterday` boucle dans le vide (loggué mais sans erreur).
2. Implémenter côté clone l'endpoint `GET/POST /api/super-admin/admins` (et `/{id}/enable|disable`) qui renvoie/crée des `AdminUser` locaux. Le central s'attend à `{admins: [{id, email, enabled, last_login_at, created_at}]}` en GET et `{admin: {...}, temporary_password: '...'}` en POST.
3. **Cron Hostinger** : configurer dans hPanel les deux jobs documentés dans `docs/DEPLOY.md` §10.

Une fois ces 3 points faits côté clones + serveur, tout l'arbre admin du central s'allume sans modification.

### 🟢 Polish / nice-to-have restants

La salve de polish du 2026-05-10 a vidé l'essentiel de cette liste (CI, lazy-load locales, Stripe locale, dama tests, audit Lucide, upload, guide page-publique, déploiement automatisé). Restes :

1. **Email templates EN restants** — seul `quote_client_en` est livré. Les autres (`contact_admin`, `callback_admin`, `quote_admin`) sont des notifications **internes** (envoyées au propriétaire) donc resteront en FR par convention. Aucune action prévue.
2. **OG image dynamique — fonts** — actuellement `OgImageController` utilise DejaVu Sans (système). Pour aligner avec la marque, on pourrait bundler une TTF Bricolage Grotesque dans `public/fonts/` et la passer à `imagettftext`. Marginal en valeur SEO mais finition cohérente.
3. **Sitemap enrichi** — actuellement liste home + pages internes. Pourrait inclure `/forfaits/{slug}` par paquet, plus la `lastmod` réelle. Voir `SeoController`.
4. **Lighthouse audit** — viser > 95 sur le score Performance/SEO/Accessibility. Aucune perf passe systématique pour l'instant ; à faire une fois le site en prod (Lighthouse CI dans le workflow).
5. **i18n keys auditer dans la CI** — script Node qui détecte les clés FR/EN désynchronisées et les clés orphelines, pour bloquer la PR si dérive.

---

## 12. Conventions absolues à respecter

(Reprises de `CLAUDE.md`, à connaître avant de toucher au code.)

- `declare(strict_types=1)` partout en PHP. Typage strict, pas de `mixed` sans nécessité.
- Vue 3 Composition API uniquement (`<script setup lang="ts">`). Pas d'Options API.
- TypeScript strict, pas de `any`.
- Pinia pour l'état partagé, `ref`/`computed` local.
- Mobile-first SCSS, jamais `max-width` comme breakpoint principal.
- Variables CSS : `var(--color-primary)`, **jamais** d'hex en dur.
- **Jamais** de chaîne FR ou EN en dur dans un composant — toujours `t('clé')` avec entrée dans **les deux** fichiers de locale.
- **Twig réservé aux emails** (`templates/emails/*.twig`). Le shell SPA est en PHP dans `SpaController`. Aucun nouveau fichier `.twig` hors `emails/`.
- Composants Vue : choisir le niveau atomic design (`atoms/`, `molecules/`, `organisms/`) **avant** de créer le fichier. Pas de bouton/champ/carte ad hoc dans une vue, on passe par les `Ui*` ou on enrichit le design system.
- Backend : services à responsabilité unique, dépendances injectées par interface, controllers fins (parsing/auth/HTTP).
- Pas de `mail()` PHP natif — Symfony Mailer uniquement.
- Pas de SQL direct — toujours via migration Doctrine.
- Pas de webhook Stripe sans validation de signature.
- Pas d'EUR — devise = CAD partout.
- Pas de référence à la France ou au RGPD — la base légale est québécoise (Loi 25, TPS/TVQ).

---

## 13. Démarrage rapide pour la prochaine session

1. Lire ce fichier (`docs/STATUS.md`) en premier.
2. Lire `CLAUDE.md` pour les conventions et l'architecture.
3. Si on touche au déploiement → `docs/DEPLOY.md`.
4. Si on touche à Stripe → `docs/STRIPE.md`.
5. Si on touche au central dashboard → `docs/CENTRAL_DASHBOARD_BRIEF.md` + ce fichier §7 et §11.
6. `./start-local.sh` pour vérifier que tout fonctionne en local avant de toucher.
7. Avant de claim un travail comme « fait » : `php bin/phpunit && npm test && npm run build && composer audit` doivent tous passer.
