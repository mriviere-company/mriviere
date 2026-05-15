# rivierematthieu.com

Site vitrine personnel de **Matthieu Rivière** — travailleur autonome au **Québec (Canada)** en création de sites web. Bilingue **FR + EN**. **Symfony 8 + Vue 3 SPA**, design audacieux, intégration Stripe en **CAD** (acompte 50 % + abonnement mensuel), petit back-office pour gérer les demandes et superviser des clones clients.

> Le site est aussi une démo vivante des compétences techniques : tout ce qui est sur la page de présentation est réellement dans le code.

---

## Stack

| Domaine | Outil |
|---|---|
| Backend | PHP 8.4, **Symfony 8.0** (controllers fins + services SOLID), Doctrine ORM 3, MariaDB 10.11+ |
| Frontend | Vue 3.5 SPA **autonome** (Composition API + TypeScript strict), Vue Router 4, Pinia, **vue-i18n 10** |
| Architecture front | **Atomic Design** (`atoms / molecules / organisms / views`) + design system maison (tokens SCSS + composants `Ui*`) |
| Build | Vite 5 + `vite-plugin-symfony` + `pentatrion/vite-bundle` 8.2 (résolution d'assets ; aucun rendu Twig dans la SPA) |
| Templating Twig | **Réservé aux emails** uniquement. Le shell HTML de la SPA est émis en PHP par `SpaController`. |
| Styles | SCSS + CSS custom properties, **mobile first**, View Transitions API |
| Paiement | Stripe Checkout (subscription + invoice item pour l'acompte) — devise **CAD** |
| Email | Symfony Mailer (SMTP Hostinger en prod) |
| Sécurité super-admin | JWT EdDSA (libsodium Ed25519), opt-in côté clones |
| Tests | PHPUnit 11, Vitest 2, Playwright |
| Hébergement | Hostinger mutualisé PHP + MariaDB |

---

## Forfaits

| Forfait | Pages | Création | Abonnement | Inclus en plus |
|---|---|---|---|---|
| **STARTER** | 1-3 | 399 $ | 29 $/mois | — |
| **STANDARD** | 1-5 | 599 $ | 49 $/mois | Galerie photos, SEO de base |
| **PREMIUM** | 1-8 | 899 $ | 79 $/mois | Blog/Actualités, rapport mensuel |

Engagement minimum 12 mois. **TPS/TVQ non applicable** (petit fournisseur, revenus annuels < 30 000 $ CAD). Acompte 50 % à la commande, solde à la livraison.

L'abonnement inclut hébergement, domaine, certificat SSL, sauvegardes automatiques, mises à jour de sécurité et modifications mineures (textes, photos, horaires).

---

## Bilinguisme

- **URLs canoniques en français** : `/forfaits`, `/profil`, `/devis`, `/contact`, `/mentions-legales`, `/cgv`. Pas de préfixe `/fr/...` ni `/en/...` sur les pages internes.
- **Home seule** a trois variantes pour le SEO et le sitemap : `/` (locale détectée), `/fr` (force FR), `/en` (force EN).
- **Détection initiale** : URL exacte (`/fr` / `/en`) > `localStorage` (`mriviere.locale`) > `navigator.language` > fallback `fr`.
- Toutes les chaînes UI sont externalisées dans `assets/locales/fr.json` et `assets/locales/en.json`.
- Switcher dans le header (bouton « Globe ») bascule entre FR et EN sans navigation.
- `sitemap.xml` est généré dynamiquement par `SeoController` avec balises `xhtml:hreflang`.
- Le back-office reste en français uniquement (interface perso).

---

## Prérequis

| Outil | Version minimale |
|---|---|
| PHP | 8.4 (extension `sodium` requise pour le super-admin) |
| Composer | 2.x |
| Node.js | 22.x |
| MariaDB | 10.11+ |
| Symfony CLI | recommandé |

---

## Installation locale

```bash
git clone https://github.com/mriviere-company/mriviere.git
cd mriviere

composer install
npm install

cp .env.example .env.local
# Adapter DATABASE_URL, APP_SECRET, MAILER_DSN, STRIPE_*, SUPER_ADMIN_*

# (Optionnel) Générer la paire EdDSA pour le super-admin dashboard
openssl genpkey -algorithm Ed25519 -out var/keys/super_admin_private.pem
openssl pkey -in var/keys/super_admin_private.pem -pubout -out var/keys/super_admin_public.pem

php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate --no-interaction
php bin/console doctrine:fixtures:load --no-interaction

# Deux terminaux :
symfony server:start --no-tls   # backend http://localhost:8000
npm run dev                     # frontend Vite http://localhost:5173
```

Raccourci : `./start-local.sh` automatise les étapes ci-dessus.

Compte admin de dev (créé par les fixtures) : `contact@rivierematthieu.com` / `change-me-in-prod` (à remplacer immédiatement en prod).

---

## Variables d'environnement

Toutes les variables sont documentées dans `.env.example`. Les plus importantes :

| Variable | Rôle |
|---|---|
| `DATABASE_URL` | Connexion MariaDB |
| `APP_SECRET` | Secret Symfony (`openssl rand -base64 32`) |
| `MAILER_DSN` | SMTP Hostinger en prod |
| `PUBLIC_BASE_URL` | URL absolue du site (success/cancel URLs Stripe, sitemap) |
| `STRIPE_SECRET_KEY` | `sk_test_…` en dev, `sk_live_…` en prod (compte Stripe **Canada**) |
| `STRIPE_PUBLIC_KEY` | `pk_test_…` / `pk_live_…` (utilisé côté front si checkout JS) |
| `STRIPE_WEBHOOK_SECRET` | `whsec_…` du webhook |
| `STRIPE_PRICE_STARTER` / `_STANDARD` / `_PREMIUM` | IDs des `Price` mensuels créés en CAD |
| `FROM_EMAIL` | Adresse expéditeur des emails |
| `ADMIN_EMAIL` | Destinataire des notifications + login admin créé par fixtures |
| `SUPER_ADMIN_PRIVATE_KEY_PATH` | Chemin PEM Ed25519 (par défaut `var/keys/super_admin_private.pem`) |
| `SUPER_ADMIN_PUBLIC_KEY_PATH` | Chemin PEM Ed25519 publique (à diffuser aux clones supervisés) |

---

## Build production

```bash
npm run build
php bin/console cache:clear --env=prod
php bin/console doctrine:migrations:migrate --env=prod --no-interaction
```

---

## Déploiement Hostinger (sans Docker)

Procédure complète dans **`docs/DEPLOY.md`**. Deux modes :

### Première mise en ligne — manuel (`docs/DEPLOY.md` §1 à §12)

Création BD + `.env.local` + `.htaccess` + SSL Let's Encrypt + premier `doctrine:fixtures:load` + webhook Stripe. À faire une fois.

### Déploiements suivants — `./deploy.sh` ou GitHub Actions (`docs/DEPLOY.md` §15)

```bash
cp .deploy.env.example .deploy.env       # remplir SSH user, host, port 65002, chemin distant
./deploy.sh --dry-run                    # valider le rsync
./deploy.sh                              # build + sync + migrations + cache:clear
./deploy.sh --release-tag=v1.2.3         # taggue dans var/log/releases.log distant
```

Sous le capot : `npm run build` + `composer install --no-dev` + `composer audit` + `rsync -avz --delete` (excludes appropriés) + commandes SSH (génération clés Ed25519 si absentes, `cache:clear`, `migrations:migrate`).

Variante GitHub Actions : `Actions → Deploy to Hostinger → Run workflow` (manual-trigger uniquement, jamais sur push pour éviter qu'un commit cassé ne parte direct). Smoke-test final sur `/`, `/sitemap.xml`, `/og.png`.

`public/.htaccess` applique les headers de sécurité (CSP, HSTS) et la redirection HTTPS. `.htaccess` racine route toute requête vers `public/index.php`.

Pourquoi pas Docker : Hostinger mutualisé n'expose pas de runtime container. Le stack PHP-FPM + MariaDB est géré par l'hébergeur, pas besoin de l'orchestrer. Si on migre un jour vers Hostinger Cloud / VPS, on peut basculer sur Docker Compose sans toucher au code applicatif.

---

## Structure du projet

```
mriviere/
├── assets/                  # Frontend Vue 3 SPA autonome
│   ├── api/                 # Client axios
│   ├── components/          # Atomic Design + design system
│   │   ├── atoms/           # UiButton, UiField, UiTag (+ __tests__)
│   │   ├── molecules/       # UiCard
│   │   └── organisms/       # SiteHeader (switcher langue), SiteFooter, CallbackBookingModal
│   ├── composables/         # useTheme
│   ├── i18n/                # vue-i18n setup + formatMoney CAD
│   ├── locales/             # fr.json + en.json (toutes les chaînes UI)
│   ├── router/              # Vue Router avec slugs FR canoniques
│   ├── stores/              # Pinia (packages, auth, quote)
│   ├── styles/              # SCSS tokens + reset + utilities + transitions + home-snap (mobile first)
│   ├── types/               # TS shared types
│   ├── views/               # Pages + sous-dossier admin/
│   ├── App.vue
│   └── main.ts
├── config/                  # Symfony config (security, services, etc.)
├── docs/
│   ├── STATUS.md            # Plan de reprise (état d'avancement)
│   ├── DEPLOY.md            # Procédure Hostinger
│   ├── STRIPE.md            # Configuration Stripe Canada
│   └── CENTRAL_DASHBOARD_BRIEF.md
├── migrations/              # Doctrine migrations
├── public/
│   ├── .htaccess            # Headers de sécurité + rewrites
│   ├── robots.txt
│   ├── build/               # Sortie Vite (gitignored)
│   └── index.php
├── src/
│   ├── Config/              # Enums (PackageSlug, QuoteStatus, MessageStatus)
│   ├── Controller/
│   │   ├── Api/             # /api/* publics (packages, quotes, contact, callbacks, profile)
│   │   ├── Admin/           # /api/admin/* (authentifiés) + ManagedSiteAdminController
│   │   ├── SpaController.php
│   │   ├── SeoController.php
│   │   └── StripeWebhookController.php
│   ├── DataFixtures/
│   ├── Dto/
│   ├── Entity/              # AdminUser, Package, Quote, ContactMessage, ProfileContent,
│   │                        # CallbackRequest, ManagedSite
│   ├── Repository/
│   ├── Security/
│   └── Service/                       # Services SOLID (1 responsabilité chacun)
│       ├── MailerService.php
│       ├── StripeService.php          # currency cad
│       ├── RequestPayloadDeserializer.php
│       └── SuperAdmin/
│           ├── JwtSigner.php          # EdDSA via libsodium
│           └── SuperAdminClient.php   # ping HTTP /api/super-admin/health
├── templates/
│   └── emails/              # Templates Mailer (HTML + texte) — seul Twig du projet
├── tests/                   # PHPUnit + e2e Playwright
├── var/keys/                # Clés EdDSA super-admin (gitignored)
├── .env.example
├── .htaccess                # Hostinger root → public/
├── CLAUDE.md
├── composer.json
├── package.json
├── tsconfig.json
├── vite.config.ts
└── README.md
```

---

## Commandes utiles

```bash
# Tests
php bin/phpunit                              # PHPUnit
npm test                                     # Vitest
npx playwright test                          # E2E (requires running server)

# Type-check
npm run type-check                           # vue-tsc

# Symfony
php bin/console debug:router
php bin/console cache:clear

# Stripe (dev)
stripe listen --forward-to localhost:8000/stripe/webhook
stripe trigger checkout.session.completed
```

---

## Documentation

- **`docs/STATUS.md`** — état d'avancement détaillé (à ouvrir en premier).
- **`docs/DEPLOY.md`** — procédure Hostinger pas-à-pas + section §15 sur le déploiement automatisé (`deploy.sh` + GitHub Actions).
- **`docs/STRIPE.md`** — configuration Stripe Canada + pattern acompte/abonnement.
- **`docs/CENTRAL_DASHBOARD_BRIEF.md`** — architecture du super-admin dashboard.
- **`docs/ADD_PUBLIC_PAGE.md`** — guide pour ajouter une page publique (route, vue, i18n FR+EN, OG meta, sitemap, tests).
- **`CLAUDE.md`** — guide pour Claude Code (conventions, règles, archi).

---

## Crédits

Conçu et codé par Matthieu Rivière. Stack open source : Symfony, Vue, vue-i18n, Stripe SDK, Lucide Icons, Bricolage Grotesque & Inter (via fontsource).

Licence : usage privé, non destiné à la redistribution.
