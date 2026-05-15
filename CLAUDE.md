# CLAUDE.md

Ce fichier guide Claude Code lorsqu'il intervient sur ce dépôt. Il décrit le **vrai** projet.

## Vue d'ensemble du projet

**rivierematthieu.com** (futur `rivierematthieu.com`) est le site personnel et la vitrine commerciale de Matthieu Rivière, **travailleur autonome au Québec (Canada)**. Le site est **bilingue français + anglais**, sert à la fois de présentation commerciale et de démonstration des compétences techniques du propriétaire — il n'est pas réutilisé pour d'autres clients.

Stack :

| Domaine | Outils |
|---|---|
| Backend | PHP 8.4, **Symfony 8.0**, Doctrine ORM 3, MariaDB 10.11+ |
| Frontend | Vue 3.5 SPA **autonome** (Composition API + TypeScript strict), Vue Router 4, Pinia, **vue-i18n 10**, Vite 5 |
| Architecture front | **Atomic Design** (`atoms/molecules/organisms`) + design system maison (tokens SCSS + composants `Ui*`) |
| Architecture back | Symfony orienté **SOLID** : controllers fins, services à responsabilité unique, DTOs validés, repositories Doctrine, dépendances injectées par interface quand pertinent |
| Build | `vite-plugin-symfony` + `pentatrion/vite-bundle` 8.2 (résolution d'assets uniquement, pas de templating Twig pour la SPA) |
| Styles | SCSS + CSS custom properties (zéro framework CSS externe), **mobile-first** |
| Paiement | Stripe Checkout (mode `subscription` + `add_invoice_items` pour l'acompte) — devise **CAD ($)** |
| Hébergement | Hostinger mutualisé (PHP + MariaDB) |
| Tests | PHPUnit 11, Vitest 2, Playwright |

Périmètre fonctionnel :

- **Vitrine publique bilingue** (URLs canoniques en FR, locale persistée en `localStorage`) : accueil, forfaits (STARTER 399 $ + 29 $/mois, STANDARD 599 $ + 49 $/mois, PREMIUM 899 $ + 79 $/mois), profil, contact, devis multi-étapes, mentions légales, CGV.
- **Devis structuré** : formulaire 4 étapes pré-rempli depuis `?pkg=...`, calcul du total côté serveur, redirection Stripe Checkout en CAD.
- **Réservation d'appel** (`CallbackRequest`) : modal accessible depuis l'accueil et la page contact, créneau via `<input type="datetime-local">`, e-mail facultatif, anti-spam honeypot.
- **Stripe** : acompte 50 % intégré comme `invoice_item` à la première facture de l'abonnement mensuel (devise `cad`).
- **Back-office** (français uniquement, usage perso) : auth Symfony Security, dashboard, devis, messages contact, demandes d'appel, abonnements (lecture seule via API Stripe), édition du contenu profil.
- **Super-admin / central dashboard** (`ManagedSite`) : pings JWT EdDSA vers les clones clients pour suivre leur santé. Voir `docs/CENTRAL_DASHBOARD_BRIEF.md`.

## Identité visuelle

**Nom de marque** : « Studio de développement web — Matthieu Rivière ».
**Symbole** : badge filaire avec hexagone bleu (fichiers SVG dans `public/img/mriviere-mark.svg` clair / `mriviere-mark-inverse.svg` sombre, plus exports PNG en 32/64/180/512/1080 px pour favicons et OG image).

**Palette** (priorité décroissante, déclarée dans `assets/styles/tokens.scss`) :

| Nom | Hex | Rôle light mode | Rôle dark mode |
|---|---|---|---|
| **Navy** | `#0F2A5F` | accent principal (`--color-accent`) | sub-accent (`--color-accent-2`) |
| **Cobalt** | `#2B5BD7` | accent secondaire (`--color-accent-2`) | accent principal (Navy est trop sombre sur Ink) |
| **Ink** | `#0A0E1A` | texte (`--color-text`) | fond (`--color-bg`) |
| **Paper** | `#FAFAF7` | fond (`--color-bg`) | texte (`--color-text`) |

Le swap Navy/Cobalt entre light et dark est fait dans `tokens.scss` via les surcharges `[data-theme='dark']` — toujours utiliser `var(--color-accent)` et `var(--color-accent-2)` dans les composants, jamais les hex en dur.

Status colors (success/warn/danger) gardent leurs couleurs sémantiques classiques (vert/ambre/rouge) — distinctes de la marque pour rester reconnaissables.

**Typographie** :
- Display (titres, identitaire) : **Bricolage Grotesque Variable** (`@fontsource-variable/bricolage-grotesque`).
- Corps : **Inter Variable**.
- Mono (eyebrows, code, étiquettes) : `ui-monospace` system fallback chain.

Toujours passer par `var(--font-display)`, `var(--font-body)`, `var(--font-mono)` — jamais déclarer une font-family en dur dans un composant.

**Animations** : View Transitions API (Chrome 115+) avec `vt-page-out` 240 ms + `vt-page-in` 280 ms ; reveal cascade au scroll via `IntersectionObserver` (`STAGGER_MS = 110`). Scroll-snap mandatoire sur la home (classe `html.home-snap`).

## Cadre fiscal et légal — Québec

- TPS/TVQ : « petit fournisseur » (revenus < 30 000 $ CAD / 4 trimestres) → pas de perception. Mention sur les factures : « TPS/TVQ non applicable — petit fournisseur ».
- Devise : **CAD ($)** uniquement. Stripe Account doit être au Canada, tous les `Price` créés en CAD.
- Loi applicable : droit civil québécois ; **Loi 25** (anciennement loi 64) pour la protection des renseignements personnels — pas le RGPD européen.
- Adresse légale : `3915 Croissant Olivier, Brossard (Québec) J4Y 2L2`. NEQ `2279489522`. Téléphone `+1 438 367 8089`.

## Internationalisation (i18n)

- **Bibliothèque** : `vue-i18n` v10 en mode Composition API. Configuration dans `assets/i18n/index.ts`.
- **Locales** : `fr` (par défaut) et `en`. Fichiers JSON dans `assets/locales/{fr,en}.json`.
- **Routes** : **URLs plates en français canonique**. Seule la home a trois variantes pour le SEO et le sitemap :
  - `/` → home, locale détectée via `localStorage` puis `navigator.language`, fallback `fr`.
  - `/fr` → home, force la locale `fr` (utilisée par hreflang/sitemap).
  - `/en` → home, force la locale `en` (utilisée par hreflang/sitemap).
  - Pages internes : `/forfaits`, `/profil`, `/devis`, `/contact`, `/mentions-legales`, `/cgv` — pas de préfixe locale, la locale vient de `localStorage`.
- **Helpers** dans `assets/router/index.ts` : `localePath('home' | 'pricing' | …)` retourne le slug FR canonique. `switchLocale(targetLocale)` met à jour la locale sans naviguer.
- **Détection initiale** : exact `/fr` ou `/en` dans le path → `localStorage` (`mriviere.locale`) → `navigator.language` → fallback `fr`.
- **Composants** : tout texte affiché passe par `t('clé')`. Jamais de chaîne FR ou EN en dur dans un `.vue`.
- **Devises** : helper `formatMoney(cents)` dans `@/i18n` qui formate en `$ CAD` selon la locale (`fr-CA` ou `en-CA`).
- **SEO** : `/sitemap.xml` (servi par `SeoController`) liste les variantes `/`, `/fr`, `/en` avec balises `xhtml:hreflang`. `public/robots.txt` autorise tout et pointe vers le sitemap.

L'admin (`/admin/*`) reste en français — c'est l'interface du propriétaire.

---

## Commandes courantes

### Développement local

```bash
# Backend
symfony server:start              # https://localhost:8000
php bin/console cache:clear
php bin/console debug:router

# Frontend
npm run dev                       # Vite hot reload sur http://localhost:5173
npm run build                     # build production dans public/build
npm run type-check                # vue-tsc

# Base de données
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
php bin/console doctrine:fixtures:load --append=false
```

Script de bootstrap rapide : `./start-local.sh` (crée la BD si nécessaire, joue les migrations + fixtures, lance Vite et le serveur Symfony en parallèle).

### Tests

```bash
# PHPUnit (35 tests : 14 unit + 13 auth-guard + 8 happy-path)
# Tests d'intégration utilisent dama/doctrine-test-bundle : chaque test est
# wrappé dans une transaction rollback à la fin → écritures DB invisibles
# entre tests. AdminWebTestCase fournit un client authentifié.
php bin/phpunit
php bin/phpunit --filter testCreateQuote

# Vitest (Vue/TS — 9 tests)
npm test
npm run test:watch

# E2E Playwright (9 tests, requiert Symfony server actif)
APP_ENV=prod symfony server:start --port=8000 --no-tls --daemon
npx playwright test
```

CI complète sur GitHub Actions (`.github/workflows/ci.yml`) : `composer audit` + migrations + fixtures + PHPUnit + vue-tsc + Vitest + build + Playwright. Service MariaDB 10.11 + Symfony CLI installé pour le serveur prod.

### Stripe (dev local — Canada)

```bash
stripe listen --forward-to localhost:8000/stripe/webhook
stripe trigger checkout.session.completed
```

### Super-admin (clés EdDSA)

```bash
# Génération initiale (une seule fois, gitignored)
openssl genpkey -algorithm Ed25519 -out var/keys/super_admin_private.pem
openssl pkey -in var/keys/super_admin_private.pem -pubout -out var/keys/super_admin_public.pem
```

---

## Architecture

### Backend (`src/`)

```
src/
├── Command/            # app:health:check-all (cron 5 min), app:stats:sync-yesterday (cron 04h)
├── Config/             # Enums (PackageSlug, QuoteStatus, MessageStatus)
├── Controller/
│   ├── Api/            # Endpoints publics (/api/packages, /api/quotes, /api/contact,
│   │                   #                    /api/callbacks, /api/profile)
│   ├── Admin/          # Back-office : Auth, Dashboard, Quote, Message, Callback,
│   │                   #               Subscription, Profile, ManagedSite, Aggregated, Upload
│   ├── SpaController.php          # Catch-all : émet le HTML d'amorçage de la SPA en PHP
│   │                              # (EntrypointRenderer + CsrfTokenManager) — aucun Twig
│   ├── OgImageController.php      # /og.png dynamique (GD, 1200×630, par route)
│   ├── SeoController.php          # /sitemap.xml dynamique (hreflang)
│   └── StripeWebhookController.php
├── DataFixtures/       # AppFixtures
├── Dto/                # Objets de requête validés
├── Entity/             # AdminUser, Package, Quote, ContactMessage, ProfileContent,
│                       # CallbackRequest, ManagedSite, SiteHealthCheck, SiteDailyStats
├── Repository/         # Repositories Doctrine
├── Security/           # JsonAuthSuccess/Failure handlers
└── Service/                       # Services SOLID, 1 responsabilité chacun
    ├── MailerService.php          # locale-aware : quote_client_en si quote.locale = en
    ├── StripeService.php          # currency 'cad', success_url avec ?locale=
    ├── RequestPayloadDeserializer.php
    └── SuperAdmin/
        ├── JwtSigner.php          # EdDSA via libsodium
        ├── SuperAdminClient.php   # health, statsSummary, listAdmins, createRemoteAdmin, …
        ├── HealthMonitor.php      # checkAll() + recordResponse() + alert email après 3 KO
        └── StatsSyncService.php   # syncYesterday() — pull stats clones, upsert SiteDailyStats
```

### Frontend (`assets/`)

Le front est une **SPA Vue 3 totalement autonome** (aucun rendu Twig dans le chemin client). Le shell HTML est émis par `SpaController` en PHP via `Pentatrion\ViteBundle\Service\EntrypointRenderer`.

L'arborescence des composants suit **Atomic Design** :

| Niveau | Rôle | Exemples actuels |
|---|---|---|
| `atoms/` | primitives sans état métier, réutilisables partout | `UiButton`, `UiField`, `UiTag` |
| `molecules/` | combinaisons d'atoms, avec un peu de structure | `UiCard` |
| `organisms/` | blocs autonomes, souvent stateful, composent une section UI | `SiteHeader`, `SiteFooter`, `CallbackBookingModal` |
| `views/` | pages (assemblage d'organisms, branchées au router) | `HomeView`, `QuoteView`, `admin/*` |

Tout nouveau composant doit choisir son niveau et y être créé. Les imports passent par les alias `@/components/atoms/*`, `@/components/molecules/*`, `@/components/organisms/*`. Les composants UI partagés (atoms + molecules) sont préfixés `Ui*` et constituent le **design system** : ils consomment uniquement les tokens (`var(--color-*)`, `var(--font-*)`, `var(--radius-*)`) déclarés dans `assets/styles/tokens.scss` — jamais d'hex ni de font-family en dur.

```
assets/
├── api/                # Client axios
├── components/
│   ├── atoms/          # UiButton, UiField, UiTag (+ __tests__/)
│   ├── molecules/      # UiCard
│   └── organisms/      # SiteHeader (switcher langue), SiteFooter, CallbackBookingModal
├── composables/        # useTheme
├── i18n/               # vue-i18n config + formatMoney helper
├── locales/            # fr.json, en.json — toutes les chaînes UI
├── router/             # Vue Router avec routes plates (slugs FR canoniques)
├── stores/             # Pinia (packages, auth, quote)
├── styles/             # tokens.scss, reset.scss, utilities.scss, transitions.scss,
│                       # home-snap.scss, index.scss
├── types/              # Types TS partagés
├── views/              # Pages publiques + admin/
├── App.vue             # Layout racine
└── main.ts             # Entry Vite
```

### Modèle de données

```
AdminUser              (1 unique seed via fixtures, ROLE_ADMIN)
Package                (slug enum, prix en cents CAD, features json, stripeMonthlyPriceId, sortOrder)
Quote                  (UUID v7, FK package, infos client, options json, totaux, status enum,
                       IDs Stripe, locale fr|en — choisi le template d'email + ?locale en success_url)
ContactMessage         (status enum unread/read/archived)
ProfileContent         (singleton, bio + stack + links + photoUrl)
CallbackRequest        (name, email opt., phone, preferredSlot, message, status enum)
ManagedSite            (host, label, publicKeyFingerprint, lastHealthStatus, lastHealthCheckedAt)
SiteHealthCheck        (FK ManagedSite, status, latencyMs, appVersion, error, checkedAt)
SiteDailyStats         (FK ManagedSite, day unique, pageViews, uniqueVisitors, topPaths json, syncedAt)
```

### Stripe — pattern acompte + abonnement (Canada)

Stripe Checkout en mode `subscription` n'autorise qu'un seul `Price` récurrent par session. Pour facturer l'acompte 50 % en plus de la première mensualité :

1. `StripeService::createCheckoutSession()` ouvre une session `mode=subscription` avec uniquement le `Price` mensuel et embarque `quote_id` + `deposit_cents` dans les `metadata`.
2. Le webhook `checkout.session.completed` lit `deposit_cents` et appelle `addDepositInvoiceItem()` qui crée un `InvoiceItem` (`currency=cad`) lié au customer + à la subscription.
3. La première facture émise comprend donc `[acompte] + [premier mois]`. Les factures suivantes ne comportent que le récurrent.

**Important** : tous les `Price` Stripe doivent être créés en CAD, sur un compte Stripe Canada.

`success_url` Stripe pointe vers `/devis/confirmation/{id}` (URL plate, pas de préfixe locale — la SPA charge la locale depuis `localStorage`).

Détails complets et procédure de mise en production dans **`docs/STRIPE.md`**.

### Central super-admin dashboard

Lecture en cascade pour suivre l'état de plusieurs clones « clients » :

1. Génération d'une paire EdDSA (Ed25519) stockée dans `var/keys/super_admin_*.pem` (gitignored).
2. `JwtSigner::sign()` produit un JWT avec `aud`, `iat`, `exp` (5 min) et `jti` (UUID v4).
3. `SuperAdminClient::ping(ManagedSite $site)` appelle `GET https://<host>/api/super-admin/health` avec le JWT en `Authorization: Bearer …`.
4. Le clone client doit déclarer la clé publique (fingerprint) dans son `.env.local` et opt-in via `SUPER_ADMIN_ENABLED=true`. Réponse typée `HealthResponse` (statuts : `ok`, `unreachable`, `unauthorized`, `forbidden`, `opt_in_off` (404), `http_error`, `invalid_body`).
5. CRUD des sites supervisés via `/api/admin/sites` (entité `ManagedSite`).

Brief détaillé dans **`docs/CENTRAL_DASHBOARD_BRIEF.md`**. Sprint 1 (signing + ping + CRUD) terminé. Sprints suivants (cron `HealthMonitor`, `SiteHealthCheck`, alertes email, stats agrégées, CRUD admin distant) listés dans **`docs/STATUS.md`**.

---

## Standards de code

### PHP / Symfony — principes SOLID
- `declare(strict_types=1)` partout
- Typage strict, pas de `mixed` sauf nécessité
- Attributs natifs Symfony 8 (`#[Route]`, `#[CurrentUser]`, `#[IsGranted]`)
- DTOs validés via Symfony Validator avant toute logique métier
- Repositories étendent `ServiceEntityRepository`
- **S — Single responsibility** : un service = une responsabilité (`MailerService` envoie des emails, `StripeService` parle à Stripe, `JwtSigner` signe des JWT). Si une classe grossit au point de réclamer un sous-titre, on la coupe.
- **O — Open/closed** : on étend par injection (nouveau gateway, nouveau handler) plutôt que par `if`/`switch` explosifs dans une classe existante.
- **L — Liskov** : les enums (`PackageSlug`, `QuoteStatus`, `MessageStatus`) et value objects ne mentent pas sur leur contrat. Toute sous-classe ou implémentation respecte les pré/postconditions de la base.
- **I — Interface segregation** : on dépend d'interfaces étroites (`CsrfTokenManagerInterface`, `MailerInterface`) plutôt que d'objets gros. Pas d'interface fourre-tout.
- **D — Dependency inversion** : autowiring via constructeur, jamais de `new SomeService()` dans un controller. Les frontières externes (Stripe SDK, HTTP client, mailer) sont encapsulées dans un service du projet.

Conséquence pratique : les controllers restent fins (parsing/auth/HTTP), les services portent la logique métier, les entités restent des modèles de données.

### Vue / TypeScript — atomic design + design system
- Vue 3 Composition API uniquement (`<script setup lang="ts">`)
- TypeScript strict, pas de `any`
- Pinia pour l'état partagé, `ref`/`computed` pour le local
- Props et emits typés
- **Atomic Design strict** : tout nouveau composant choisit son niveau (`atoms/`, `molecules/`, `organisms/`) avant d'être créé. Un atom ne dépend d'aucun autre composant. Un molecule peut composer des atoms. Un organism peut composer atoms + molecules. Une view peut composer les trois.
- **Design system** : les composants `Ui*` (atoms/molecules) sont les seules briques visuelles partagées. Pas de duplication de bouton/champ/carte ad hoc dans les vues — on étend `Ui*` ou on crée un nouvel atom/molecule réutilisable.
- **Mobile first** : SCSS écrit du plus petit au plus grand, jamais `max-width` comme breakpoint principal
- Variables CSS : `var(--color-accent)`, jamais d'hex en dur. Idem fonts (`var(--font-display)`, `var(--font-body)`, `var(--font-mono)`).
- **i18n obligatoire** : chaque chaîne UI passe par `t()`. Toute nouvelle vue ajoute ses clés à `fr.json` ET `en.json`.

### Tests
- Un fichier source = un fichier de test
- Mocks aux frontières (axios, mailer, Stripe SDK)
- E2E sur les flux critiques en FR ET EN

---

## Règles absolues

- **Jamais de SQL direct** — toujours via migration Doctrine.
- **Twig est réservé aux emails** (`templates/emails/*.twig`). Le shell HTML de la SPA est émis en PHP par `SpaController` ; aucun autre fichier `.twig` ne doit exister hors du dossier `emails/`.
- **Jamais de webhook Stripe sans validation de signature** (`Webhook::constructEvent`).
- **Jamais de `mail()` PHP natif** — Symfony Mailer uniquement.
- **Jamais de variable CSS en dur dans un `.vue`** — toujours `var(--…)`.
- **Jamais de chaîne FR ou EN en dur dans un composant** — toujours `t('clé')` avec entrée dans les deux fichiers de locale.
- **Jamais d'EUR** — devise du site = CAD.
- **Jamais de référence à la France ou au RGPD** dans le contenu visible — le site est québécois (Loi 25, TPS/TVQ).
- **Jamais de préfixe `/fr/...` ou `/en/...` sur les pages internes** — les slugs sont en FR, la locale vit dans `localStorage`. Seule exception : `/fr` et `/en` pour la home (SEO/hreflang).

---

## Documentation

| Fichier | Contenu |
|---|---|
| `docs/STATUS.md` | Plan de reprise : ce qui est fait / ce qui reste (priorisé). À ouvrir en premier après un redémarrage de session. |
| `docs/DEPLOY.md` | Procédure Hostinger : §1-§12 manuel pour la première mise en ligne, §15 pour le déploiement automatisé via `deploy.sh` ou GitHub Actions. |
| `docs/STRIPE.md` | Configuration Stripe Canada, création des `Price` CAD, webhook, migration test → live. |
| `docs/CENTRAL_DASHBOARD_BRIEF.md` | Architecture détaillée du super-admin dashboard. |
| `docs/ADD_PUBLIC_PAGE.md` | Checklist 8 étapes pour ajouter une page publique (URL FR canonique, vue Vue 3, route, i18n parité FR+EN, header/footer, OG meta dans `SpaController::resolveOgMeta`, sitemap, tests). |

---

## Déploiement Hostinger (résumé)

**Première mise en ligne** (manuel, à faire une fois) — `docs/DEPLOY.md` §1 à §12 :
1. Création BD MariaDB + utilisateur dédié
2. Upload SFTP (vendor/ inclus, sauf node_modules / var/cache / var/log / .git / tests)
3. `cp .env.example .env.local` puis renseigner DATABASE_URL Hostinger, MAILER_DSN SMTP, STRIPE_*, APP_SECRET, PUBLIC_BASE_URL=https://rivierematthieu.com, SUPER_ADMIN_*
4. `php8.4 bin/console doctrine:migrations:migrate --no-interaction --env=prod`
5. `php8.4 bin/console doctrine:fixtures:load --append=false --env=prod` (1ère fois seulement)
6. Stripe dashboard → webhook → URL `https://rivierematthieu.com/stripe/webhook` (`checkout.session.completed`, `customer.subscription.created`, `invoice.payment_failed`)
7. SSL Let's Encrypt activé dans hPanel + vérifier `.htaccess` racine + `public/.htaccess` (HTTPS, CSP, HSTS)

**Déploiements suivants** (automatisé) — `docs/DEPLOY.md` §15 :

```bash
cp .deploy.env.example .deploy.env       # remplir SSH user/host/port/path/php-bin
./deploy.sh --dry-run                    # valider le rsync
./deploy.sh                              # build + sync + migrations + cache:clear
```

Variante GitHub Actions : `Actions → Deploy to Hostinger → Run workflow` (manual-trigger uniquement). Secrets requis : `HOSTINGER_SSH_KEY`, `HOSTINGER_SSH_USER`, `HOSTINGER_SSH_HOST`, `HOSTINGER_SSH_PORT`, `HOSTINGER_REMOTE_PATH`, `HOSTINGER_PHP_BIN` (optionnel).

**Pas de Docker** : Hostinger mutualisé n'expose pas de runtime container — c'est un stack LAMP partagé. `deploy.sh` utilise `rsync` + SSH, ce qui tient en un script lisible et coûte zéro infra.
