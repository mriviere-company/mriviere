# Déploiement Hostinger — guide pas à pas

> Ce guide couvre la mise en ligne de **www.rivierematthieu.com** sur Hostinger
> mutualisé PHP + MariaDB. Le projet n'a **jamais été déployé en production**
> au moment de l'écriture — vérifier chaque étape sur l'environnement réel.

> **TL;DR pour les déploiements récurrents** : voir §15 (`deploy.sh` + GitHub Actions). Le reste du document est une référence pour la **première mise en ligne** et pour comprendre ce que `deploy.sh` fait sous le capot.

---

## 1. Prérequis chez Hostinger

| Ressource | Plan minimum | Note |
|---|---|---|
| PHP | **8.4+** | Activable dans le panel Hostinger |
| MariaDB | 10.11+ | Inclus dans tous les plans |
| Domaine | rivierematthieu.com | Acheté ou pointé via DNS |
| SSL Let's Encrypt | inclus | Activé automatiquement |
| SSH | optionnel mais recommandé | Plan Business+ |

Vérifier dans hPanel → **Avancé → Version PHP** que **8.4** est sélectionné. Si seulement 8.3 dispo, le `composer.json` accepte `>=8.3` donc ça marchera, mais on n'aura pas les nouvelles fonctions PHP 8.4.

---

## 2. Préparer le bundle local

```bash
# Sur ta machine de dev
git pull origin master           # toujours partir d'un état propre
npm ci                           # deps frontend
composer install --no-dev --optimize-autoloader --classmap-authoritative

# Build de la SPA
npm run build

# Vérifier que public/build/.vite/manifest.json existe
ls -la public/build/.vite/

# Cache prod (servira de base au upload)
APP_ENV=prod APP_DEBUG=0 php bin/console cache:warmup --no-interaction
```

À ce stade tu as :
- `vendor/` (production, sans dev deps)
- `public/build/` (assets buildés avec hashes)
- `var/cache/prod/` (Twig + container compilés)

---

## 3. Base de données chez Hostinger

1. **hPanel → Bases de données → MySQL**
2. Créer une base :
   - Nom : `uXXX_mriviere` (Hostinger ajoute son préfixe)
   - Utilisateur : `uXXX_app` avec un mot de passe fort (l'enregistrer)
   - Tous les privilèges sur la base
3. Récupérer le hostname (souvent `srvXXX.hstgr.io` ou `localhost` selon le plan)
4. Construire `DATABASE_URL` :
   ```
   mysql://uXXX_app:STRONG_PWD@srvXXX.hstgr.io:3306/uXXX_mriviere?serverVersion=10.11-MariaDB&charset=utf8mb4
   ```

---

## 4. Comptes / accès tiers à créer

### Email Hostinger pour `contact@rivierematthieu.com`

1. hPanel → **Emails → Comptes email** → créer `contact@rivierematthieu.com`
2. Définir un mot de passe fort
3. Récupérer config SMTP : `smtp.hostinger.com:465` SSL
4. `MAILER_DSN` :
   ```
   smtp://contact%40rivierematthieu.com:STRONG_PWD@smtp.hostinger.com:465?encryption=ssl
   ```
   Note le `%40` pour le `@` dans l'URL.

### Stripe webhook

1. Dashboard Stripe (compte Canada) → **Developers → Webhooks → Add endpoint**
2. URL : `https://www.rivierematthieu.com/stripe/webhook`
3. Événements :
   - `checkout.session.completed`
   - `customer.subscription.created`
   - `invoice.payment_failed`
4. Copier le `whsec_…` → `STRIPE_WEBHOOK_SECRET` dans `.env.local`

### Clés Ed25519 pour le central dashboard

Si tu déploies le central dashboard :
- La clé privée `var/keys/super_admin_private.pem` reste sur Hostinger uniquement
- Vérifier `chmod 600` après upload SFTP
- Pour chaque clone client à monitorer, distribuer la clé publique `var/keys/super_admin_public.pem` par SCP

---

## 5. Upload SFTP

Récupérer les infos SFTP dans hPanel → **Fichiers → FTP**.

**À uploader** (tout sauf ce qui suit) :
- `node_modules/` ❌
- `var/cache/dev/` ❌
- `var/cache/test/` ❌
- `var/log/*` ❌ (vide il sera recréé)
- `var/sessions/` ❌
- `.git/` ❌
- `tests/` ❌ (pas en prod)

**Hiérarchie cible Hostinger** :
```
public_html/
├── (contenu du dossier public/ du projet)
└── ...

application/                ← un niveau au-dessus de public_html, hors web root
├── src/
├── config/
├── templates/
├── vendor/
├── var/
├── migrations/
├── ...
└── bin/
```

⚠ **Important** : seul le contenu de `public/` doit être à la racine web. Le reste va **au-dessus** de `public_html/`. Si Hostinger ne permet pas ça (certains plans), il faut bricoler un `.htaccess` qui rewrite tout vers `public/index.php`. Le `public/.htaccess` du projet le fait déjà — vérifier que les paths absolus dans `public/index.php` soient corrects (cf. `require_once dirname(__DIR__).'/vendor/autoload_runtime.php';`).

### Variante simple (un seul dossier `public_html/`)

Si Hostinger ne permet qu'un seul dossier web :
1. Uploader **tout le projet** dans `public_html/`
2. Le `.htaccess` à la racine (déjà committé) redirige toutes les requêtes vers `public/index.php`
3. Bloquer l'accès aux fichiers sensibles via les `<FilesMatch>` du `.htaccess` racine

---

## 6. Configurer `.env.local` sur le serveur

Connecter via File Manager ou SFTP, créer/éditer `.env.local` à la racine de l'app :

```ini
APP_ENV=prod
APP_SECRET=GENERATE_WITH_OPENSSL_RAND_BASE64_32

DATABASE_URL="mysql://uXXX_app:STRONG_PWD@srvXXX.hstgr.io:3306/uXXX_mriviere?serverVersion=10.11-MariaDB&charset=utf8mb4"

MAILER_DSN=smtp://contact%40rivierematthieu.com:STRONG_PWD@smtp.hostinger.com:465?encryption=ssl

PUBLIC_BASE_URL=https://www.rivierematthieu.com
FROM_EMAIL=contact@rivierematthieu.com
ADMIN_EMAIL=contact@rivierematthieu.com

STRIPE_SECRET_KEY=sk_live_<remplacer-par-votre-cle-secrete>
STRIPE_WEBHOOK_SECRET=whsec_<remplacer-par-votre-secret-webhook>
STRIPE_PRICE_STARTER=price_<id-stripe-starter>
STRIPE_PRICE_STANDARD=price_<id-stripe-standard>
STRIPE_PRICE_PREMIUM=price_<id-stripe-premium>
STRIPE_DASHBOARD_BASE=https://dashboard.stripe.com

SUPER_ADMIN_ISSUER=https://www.rivierematthieu.com
SUPER_ADMIN_PRIVATE_KEY=%kernel.project_dir%/var/keys/super_admin_private.pem

LOCK_DSN=flock
```

Penser à uploader **`var/keys/`** avec la paire Ed25519 si on veut le central dashboard. `chmod 600` sur la clé privée.

---

## 7. Initialiser la base de données

### Si tu as un accès SSH

```bash
cd /path/to/application
php bin/console doctrine:migrations:migrate --no-interaction --env=prod
php bin/console doctrine:fixtures:load --no-interaction --env=prod   # PREMIÈRE FOIS UNIQUEMENT
php bin/console cache:clear --env=prod
```

### Si pas de SSH (plan mutualisé basique)

1. Sur ta machine locale, créer un dump SQL :
   ```bash
   mysqldump -u edr mriviere > /tmp/mriviere-init.sql
   ```
2. **Alternative cleaner** : faire tourner les migrations en local contre une base vide qui correspond à la prod, puis dumper :
   ```bash
   mysql -u edr -e "DROP DATABASE IF EXISTS mriviere_prod_init; CREATE DATABASE mriviere_prod_init"
   php bin/console --env=prod doctrine:migrations:migrate --no-interaction
   php bin/console --env=prod doctrine:fixtures:load --no-interaction
   mysqldump -u edr mriviere_prod_init > /tmp/mriviere-init.sql
   ```
3. Uploader `mriviere-init.sql` via SFTP
4. hPanel → **Bases de données → phpMyAdmin** → **Importer** → choisir le fichier
5. Vérifier que toutes les tables sont créées

⚠ Le compte admin créé par les fixtures a le mot de passe `change-me-in-prod`. **Le changer immédiatement** après le premier login (`/admin/login`) via `/admin/settings`. Ou mieux : générer un nouveau hash localement et faire un UPDATE SQL.

---

## 8. Configuration `.htaccess`

Le projet committe déjà :
- `.htaccess` à la racine (rewrite vers `public/`)
- `public/.htaccess` (HTTPS forcé, headers de sécurité, CSP, rewrite vers `index.php`)

Vérifier que `mod_rewrite` et `mod_headers` sont activés chez Hostinger (en général oui par défaut).

Tester :
```
https://www.rivierematthieu.com/                     → 200, home FR
https://www.rivierematthieu.com/fr                   → 200, home FR
https://www.rivierematthieu.com/en                   → 200, home EN
https://www.rivierematthieu.com/forfaits             → 200, page forfaits
https://www.rivierematthieu.com/sitemap.xml          → 200, XML
https://www.rivierematthieu.com/robots.txt           → 200, texte
https://www.rivierematthieu.com/api/packages         → 200, JSON
https://www.rivierematthieu.com/.env                 → 403 (bloqué)
```

---

## 9. SSL / HTTPS

hPanel → **Avancé → SSL** → activer Let's Encrypt pour `rivierematthieu.com` ET `www.rivierematthieu.com`. Renouvellement automatique inclus.

Le `public/.htaccess` force HTTPS via 301 — donc même si quelqu'un tape `http://` il sera redirigé.

---

## 10. Cron Hostinger — central dashboard

Les commandes existent désormais (Sprint 2 partiel livré). Configure-les dans hPanel → **Avancé → Tâches Cron**.

Remplace `/home/u123456789/domains/rivierematthieu.com/public_html` par le chemin réel de ton site (`pwd` en SSH te le donne).

```cron
# Health check toutes les 5 minutes
*/5 * * * * cd /home/u123456789/domains/rivierematthieu.com/public_html && /usr/bin/php8.4 bin/console app:health:check-all --env=prod --no-interaction >> var/log/cron.log 2>&1

# Stats sync chaque jour à 04h00 (heure du Québec)
0 4 * * * cd /home/u123456789/domains/rivierematthieu.com/public_html && /usr/bin/php8.4 bin/console app:stats:sync-yesterday --env=prod --no-interaction >> var/log/cron.log 2>&1
```

Notes :
- Hostinger expose plusieurs binaires PHP (`php8.4`, `php8.3`, etc.). On force `php8.4` car Symfony 8 le requiert. Vérifie `/usr/bin/php-config-list` ou via SSH `which php8.4`.
- La sortie est redirigée vers `var/log/cron.log` (créer le dossier au besoin) au lieu de `/dev/null`, pour qu'on puisse débugger un cron silencieux.
- `--no-interaction` est obligatoire en cron (Symfony bloque sinon sur les prompts).
- Le health check stocke chaque ping dans `site_health_checks` et envoie un email à `ADMIN_EMAIL` après 3 échecs consécutifs (cf. `App\Service\SuperAdmin\HealthMonitor`).

Pour tester manuellement avant d'activer le cron :

```bash
ssh u123…@rivierematthieu.com
cd ~/domains/rivierematthieu.com/public_html
php8.4 bin/console app:health:check-all --env=prod -v
```

La commande sort un tableau récapitulatif site par site (status, latence, alerte envoyée).

---

## 11. Validation finale (smoke tests)

1. Ouvrir `https://www.rivierematthieu.com/` → home s'affiche en dark, slab gradient visible
2. Toggle thème → bascule en light, favicon swap
3. Aller sur `/forfaits` → voir les 3 forfaits avec les vrais Price IDs en BD
4. Cliquer « Choisir STANDARD » → arriver sur `/devis?pkg=standard` étape 2
5. Remplir le formulaire → soumettre → redirection Stripe Checkout
6. Utiliser carte test `4242 4242 4242 4242` (en mode test) ou vraie carte (live)
7. Après paiement, retour sur `/devis/confirmation/{id}` → devis marqué `deposit_paid`
8. Vérifier que tu as reçu **deux emails** : confirmation client + notification toi
9. Login admin → `/admin/login` → voir le devis dans la liste
10. Tester `/contact`, `/profil`, `/cgv`, `/mentions-legales` un par un
11. Tester le modal « Réserver un appel » sur la home → vérifier que la demande arrive bien dans `/admin/callbacks` + email reçu

Si tout passe : **bravo, le site est en ligne** 🚀

---

## 12. Cas d'erreur fréquents

| Symptôme | Cause probable | Fix |
|---|---|---|
| Page 500 partout | `var/cache/prod` mal généré | `php bin/console cache:clear --env=prod` après upload |
| `Class not found` | autoload obsolète | `composer dump-autoload --classmap-authoritative --no-dev` puis re-upload `vendor/composer/` |
| Stripe webhook 401 | `whsec_…` mal copié | re-vérifier `STRIPE_WEBHOOK_SECRET` dans `.env.local` |
| Email jamais reçu | `MAILER_DSN` ou DNS SPF | tester avec `php bin/console messenger:consume async -vv` (si Messenger activé) ou `mailer:test` |
| Favicon ne change pas | cache navigateur agressif | fermer/rouvrir l'onglet, ou `chrome://favicon/...` reset |
| `/sitemap.xml` retourne du HTML SPA | priority de route mal résolue | vérifier que `SeoController` est bien chargé (`php bin/console debug:router seo_sitemap`) |
| Migrations échouent | charset BD pas en utf8mb4 | recréer la base avec `CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci` |

---

## 13. Mise à jour ultérieure

Une fois que la première mise en ligne est faite (sections 1 à 12), les déploiements suivants passent par **§15 (`deploy.sh` ou GitHub Actions)**. Cette section reste comme référence manuelle si jamais l'automatisation casse.

```bash
# Local
git pull && composer install --no-dev --optimize-autoloader && npm ci && npm run build

# Upload SFTP : public/build/* + le code modifié + les nouvelles migrations

# Prod (SSH)
php bin/console doctrine:migrations:migrate --no-interaction --env=prod
php bin/console cache:clear --env=prod
```

Si pas de SSH : faire les migrations via un dump SQL incrémental.

---

## 14. Backup

hPanel → **Sauvegarde** → activer les backups automatiques quotidiens. Les backups Hostinger couvrent fichiers + DB.

En complément, faire un dump manuel après chaque release majeure :
```bash
mysqldump -u uXXX_app -p uXXX_mriviere > backup_$(date +%F).sql
```

---

## 15. Déploiement automatisé (sans Docker)

Hostinger mutualisé n'expose pas Docker. Le projet utilise donc **`rsync` + `ssh`** sur l'environnement LAMP partagé. Deux entrées disponibles, qui partagent le même script :

### 15.1 Depuis ta machine — `./deploy.sh`

1. Copier `.deploy.env.example` → `.deploy.env` (gitignored) et remplir tes credentials Hostinger (SSH user, host, port `65002`, chemin distant, binaire PHP).
2. Lancer :

```bash
./deploy.sh                  # build + sync + migrations + cache:clear
./deploy.sh --dry-run        # rsync --dry-run, aucune commande distante
./deploy.sh --no-build       # si public/build/ et vendor/ sont déjà à jour
./deploy.sh --skip-migrations
./deploy.sh --release-tag=v1.2.3
```

Ce que fait `deploy.sh` (cf. le script lui-même pour le détail) :

- `npm run build` + `composer install --no-dev --optimize-autoloader` en local
- `composer audit` (ABORT si vulnérabilité connue)
- `rsync -avz --delete` vers le remote en excluant `.git`, `node_modules`, caches, logs, `.env.local`, `tests/`, `public/uploads/*/*` (préserve les fichiers déjà uploadés)
- Sur le remote, via SSH :
  - Génère la paire Ed25519 dans `var/keys/` si absente (super-admin dashboard)
  - Vérifie la présence de `.env.local` (warn si manquant)
  - `php bin/console cache:clear --env=prod`
  - `php bin/console doctrine:migrations:migrate --no-interaction --env=prod`
  - Append `var/log/releases.log` avec la date UTC + le tag de release

### 15.2 Depuis GitHub — `Actions → Deploy to Hostinger → Run workflow`

Workflow `.github/workflows/deploy.yml`, **manual-trigger uniquement** (pas de deploy automatique sur push pour éviter qu'un commit cassé ne parte direct). Inputs :

- `release_tag` : libellé du release log (default = SHA court)
- `skip_migrations` : sauter les migrations Doctrine

Secrets GitHub à définir (Settings → Secrets and variables → Actions) :

| Secret | Valeur |
|---|---|
| `HOSTINGER_SSH_KEY` | clé privée Ed25519 (ne contient PAS le passphrase) |
| `HOSTINGER_SSH_USER` | `u123…` |
| `HOSTINGER_SSH_HOST` | `rivierematthieu.com` |
| `HOSTINGER_SSH_PORT` | `65002` |
| `HOSTINGER_REMOTE_PATH` | `/home/u123…/domains/rivierematthieu.com/public_html` |
| `HOSTINGER_PHP_BIN` | `/usr/bin/php8.4` (optionnel, default fait l'affaire) |

La clé publique correspondante doit être ajoutée dans hPanel → **Avancé → Accès SSH → Gérer les clés SSH**.

### 15.3 Première mise en ligne

`deploy.sh` suppose que **§3 à §9 sont déjà faits manuellement** (création de la BD, `.env.local` rempli sur le remote, `.htaccess` en place, SSL activé, premier `doctrine:fixtures:load` joué). Il automatise ensuite tous les déploiements suivants.

Pour une mise en ligne propre :
1. Faire la première mise en ligne **manuellement** en suivant §1 à §12.
2. Tester que le site marche.
3. Configurer `.deploy.env` ou les secrets GitHub.
4. Lancer `./deploy.sh --dry-run` pour valider le rsync.
5. À partir de là, tous les déploiements passent par `./deploy.sh` ou la GH Action.

### 15.4 Pourquoi pas Docker ?

- Hostinger mutualisé ne fournit pas de runtime container — il faudrait un VPS.
- Le stack (PHP-FPM + MariaDB partagés) est déjà géré par l'hébergeur, pas besoin de l'orchestrer.
- `rsync` + SSH coûte zéro infra et tient en un script lisible.
- Si on migre un jour vers un VPS Hostinger Cloud / KVM, on peut basculer sur Docker Compose sans toucher au code applicatif.
