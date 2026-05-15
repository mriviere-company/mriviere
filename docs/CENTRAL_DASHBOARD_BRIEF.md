# Brief : dashboard central `www.rivierematthieu.com`

> **Comment utiliser ce fichier** : copie son contenu comme premier message
> à un nouveau Claude Code dans le repo `rivierematthieu-central` (ou
> équivalent). Il contient tout le contexte nécessaire — pas besoin de
> consulter le repo `Base-Template` séparément, sauf pour vérifier un
> détail d'implémentation côté clone.

---

## 0. Contexte

Je gère deux projets liés :

1. **WebBase** (`Base-Template` repo) — un socle Symfony 7 + Vue 3 que je
   clone pour livrer des sites vitrines clé-en-main à des clients (TPE,
   artisans, professions libérales). Chaque clone est une instance
   indépendante (BDD séparée, vhost séparé) qui expose **optionnellement**
   une API JSON sécurisée `/api/super-admin/*` consommable par un
   dashboard central.

2. **`www.rivierematthieu.com`** (CE repo) — mon site personnel + le
   **dashboard central** qui consomme l'API super-admin de tous mes sites
   clients pour : monitorer leur santé, agréger leurs stats, gérer les
   accès admin (créer/désactiver des comptes admin sur n'importe quel
   clone à distance).

Le showcase public de WebBase tourne sur `exemple.rivierematthieu.com` et
sera lui aussi consommé par ce central comme s'il était un client.

---

## 1. Mission

Construire le **dashboard central** : un site web qui, à partir d'une auth
admin (moi), permet de :

1. **Lister mes sites clients** (registre interne).
2. **Vérifier la santé** de chaque site (cron périodique pingant
   `/api/super-admin/health`).
3. **Agréger les stats** de chaque site (cron pulling
   `/api/super-admin/stats/summary` et `/api/super-admin/stats/timeseries`).
4. **Gérer les comptes admin** côté chaque clone (lister/créer/désactiver
   via `/api/super-admin/access/admins`).
5. **Servir aussi mon site personnel** (about, services, contact —
   pages marketing standard).

Tout passe par des **JWT EdDSA** que ce central signe avec sa clé privée.
Chaque clone détient la clé publique pour vérifier les tokens. La clé
privée NE QUITTE JAMAIS ce central.

---

## 2. Non-goals (explicitement hors scope)

- ❌ Modifier le code de WebBase (le clone). On ne touche **que** ce repo.
- ❌ Implémenter un mécanisme d'auth différent côté clone — le contrat
  JWT EdDSA est figé.
- ❌ Édition de contenu CMS via l'API super-admin. Si je veux modifier
  un bloc d'un client, je m'authentifie comme admin de ce client (auth
  session normale) — pas de cross-tenant content via le central.
- ❌ Hébergement multi-tenant des données client. Chaque clone garde sa
  BDD ; le central ne réplique que ce qu'il a explicitement pull (stats
  agrégées + liste admins).

---

## 3. Stack recommandée

Symfony 7 + Vue 3 + TS strict + Pinia + Vite, **identique** au socle
WebBase. Avantages :

- Je peux réutiliser le design system mobile-first de WebBase
  (`assets/design-system/index.scss`, classes `wb-*`).
- Je peux réutiliser le pattern d'auth admin (`json_login` Symfony +
  cookie session + Pinia store `auth`).
- Une seule stack à maintenir.

Si tu veux partir sur Next.js / autre, c'est un choix qui m'irait aussi
mais demande de redire tout le brief en termes équivalents.

Conventions à respecter (cohérence avec WebBase) :
- PHP 8.4, `declare(strict_types=1)`, services `final readonly` où
  pertinent.
- Vue 3 Composition API uniquement (`<script setup lang="ts">`).
- Pinia stores qui retournent `{ ok: boolean, error?: string }`.
- Tests Vitest pour tout composant Vue + PHPUnit pour les services.
- BEM avec préfixe `wb-` pour les classes CSS.

---

## 4. Contrat API à consommer (côté clone WebBase)

Toutes ces routes existent **déjà** sur chaque clone, derrière le firewall
`super_admin_api`. Tu n'as rien à coder côté clone — juste les appeler.

### 4.1 Authentification

`Authorization: Bearer <JWT EdDSA>` sur chaque requête. Le clone vérifie
la signature avec **sa** copie de la clé publique du central, puis vérifie
les claims (`iss`, `aud`, `exp`, `nbf`, `jti`, `scope`).

Tout échec → `401 Unauthorized` avec body :
```json
{ "error": "invalid_token" }
```
ou `{ "error": "expired" }`, `{ "error": "replayed" }`,
`{ "error": "audience_mismatch" }`.

Toute scope manquant pour la route → `403 Forbidden` :
```json
{ "error": "scope_required", "scope": "stats:read" }
```

### 4.2 Endpoints

| Méthode | URL | Scope | Réponse 200 |
|---|---|---|---|
| GET | `/api/super-admin/health` | `health:read` | `{ app_version, php_version, database_ok, free_disk_bytes, observed_at }` |
| GET | `/api/super-admin/stats/summary?from=YYYY-MM-DD&to=YYYY-MM-DD` | `stats:read` | `{ period, page_views_total, unique_paths, top_paths }` |
| GET | `/api/super-admin/stats/timeseries?from=&to=` | `stats:read` | `{ period, metric: "page_views", series: [{ date, count }] }` |
| GET | `/api/super-admin/access/admins` | `access:write` | `[{ id, email, roles, enabled, lastLoginAt, createdAt }]` |
| POST | `/api/super-admin/access/admins` | `access:write` | `{ id, email, ..., temporary_password }` (mot de passe one-time visible une fois) |
| POST | `/api/super-admin/access/admins/{id}/disable` | `access:write` | objet admin mis à jour |
| POST | `/api/super-admin/access/admins/{id}/enable` | `access:write` | objet admin mis à jour |

Body POST `/admins` :
```json
{ "email": "nouveau@client.fr" }
```

### 4.3 Si l'opt-in super-admin est désactivé sur un clone

Toutes les routes répondent `404 not_found` — pas `401`, pas `403`.
Le clone fait comme si elles n'existaient pas. C'est intentionnel
(anti-fingerprinting). Côté central, il faut donc traiter `404` sur
`/api/super-admin/health` comme « clone éteint ou opt-in OFF » et
non pas comme « endpoint cassé ».

---

## 5. Format JWT EdDSA

### 5.1 Header

```json
{ "alg": "EdDSA", "typ": "JWT" }
```

### 5.2 Payload (claims)

```json
{
  "iss": "https://www.rivierematthieu.com",
  "aud": "exemple.rivierematthieu.com",
  "exp": 1762000000,
  "nbf": 1761999700,
  "jti": "a1b2c3d4-e5f6-...",
  "scope": "stats:read"
}
```

- `iss` (issuer) : URL absolue du central, doit matcher la valeur
  configurée côté clone (`super_admin.yaml` → `central_url`).
- `aud` (audience) : le `Host` HTTP de la requête (typiquement le domaine
  du clone). Doit matcher `Host:` envoyé.
- `exp` / `nbf` : Unix timestamps. Tolérance d'horloge : 30s par défaut
  côté clone (`clock_skew_seconds`).
- `jti` (JWT ID) : identifiant unique. Le clone le mémorise pendant
  600s par défaut (`jti_replay_window_seconds`) ; tout rejeu dans cette
  fenêtre est rejeté `401 replayed`. **Génère un UUID v4 fresh à chaque
  appel**.
- `scope` : string séparée par espaces (`"stats:read access:write"`)
  ou tableau JSON. Limite la portée du token aux opérations strictement
  nécessaires.

### 5.3 Recommandations TTL

- TTL court (60-120s) pour les appels ad-hoc.
- TTL long (5-10 min) pour les batch (cron qui pull stats sur N sites).
- Toujours `nbf` = `now - 5s` pour absorber les dérives.

### 5.4 Bibliothèque PHP de signature

Recommandé : **`paragonie/paseto`** ou **`web-token/jwt-framework`**.
La signature Ed25519 est faite via `sodium_crypto_sign_detached()`.

Squelette minimal :
```php
$privateKey = sodium_base642bin($base64Key, SODIUM_BASE64_VARIANT_ORIGINAL);
$header = ['alg' => 'EdDSA', 'typ' => 'JWT'];
$payload = [...];
$signingInput = base64UrlEncode(json_encode($header)) . '.'
              . base64UrlEncode(json_encode($payload));
$signature = sodium_crypto_sign_detached($signingInput, $privateKey);
$jwt = $signingInput . '.' . base64UrlEncode($signature);
```

---

## 6. Crypto setup

### 6.1 Génération de la paire (UNE FOIS, sur le central)

```bash
openssl genpkey -algorithm Ed25519 -out var/keys/super_admin_private.pem
openssl pkey -in var/keys/super_admin_private.pem -pubout -out var/keys/super_admin_public.pem
chmod 600 var/keys/super_admin_private.pem
```

`super_admin_private.pem` → reste sur le central, **jamais committé,
jamais SCP vers un clone**. Stocké en `var/keys/` (gitignore).

`super_admin_public.pem` → distribué à chaque clone par SCP au
provisioning :
```bash
scp super_admin_public.pem deploy@client.fr:/var/www/sites/client.fr/config/super_admin_public.pem
```

### 6.2 Distribution

Chaque entrée du registre des sites côté central porte le fingerprint
SHA-256 de la clé publique distribuée. À l'ajout d'un site :
1. Demande à l'admin du clone le fingerprint de la clé publique installée
   (ou regénère un fingerprint local depuis la clé pour comparer).
2. Stocke le fingerprint en BDD côté central.
3. À chaque appel, on s'assure de n'utiliser que la clé qui a ce
   fingerprint — protection contre une clé corrompue.

### 6.3 Rotation

Pas de rotation automatique pour v1. Procédure manuelle :
1. Génère une nouvelle paire.
2. SCP la nouvelle clé publique sur tous les clones (renomme l'ancienne
   en `_old`).
3. Bascule le central sur la nouvelle clé privée.
4. Vérifie que tous les clones répondent OK.
5. Supprime l'ancienne clé sur les clones après 24h.

À documenter dans `docs/KEY_ROTATION.md` (à créer dans ce repo central).

---

## 7. Architecture proposée (sprint 1)

### 7.1 Entités

```php
// src/Entity/ManagedSite.php
class ManagedSite {
    int $id;
    string $domain;          // 'exemple.rivierematthieu.com'
    string $label;           // 'Showcase'
    string $centralUrl;      // 'https://www.rivierematthieu.com' (iss à signer)
    string $publicKeyFingerprint; // SHA-256 hex de la clé publique distribuée
    bool $enabled;
    DateTimeImmutable $addedAt;
    ?DateTimeImmutable $lastSeenAt;  // dernier ping /health OK
    ?string $lastHealthError;
}

// src/Entity/SiteHealthCheck.php (historique)
class SiteHealthCheck {
    int $id;
    ManagedSite $site;
    DateTimeImmutable $checkedAt;
    bool $ok;
    int $httpStatus;
    ?int $latencyMs;
    ?string $errorReason;
}

// src/Entity/SiteDailyStats.php (cache des stats pull)
class SiteDailyStats {
    int $id;
    ManagedSite $site;
    DateTimeImmutable $day;
    int $pageViewsTotal;
    int $uniquePaths;
    array $topPaths; // JSON {path, count}[]
}

// src/Entity/CentralAdmin.php (auth du central — un seul user, c'est moi)
class CentralAdmin implements UserInterface, PasswordAuthenticatedUserInterface {
    int $id;
    string $email;
    string $passwordHash;
    array $roles = ['ROLE_CENTRAL_ADMIN'];
    ?DateTimeImmutable $lastLoginAt;
}
```

### 7.2 Services

```php
// src/Service/JwtSigner.php — signe les tokens EdDSA
final readonly class JwtSigner {
    public function sign(ManagedSite $site, string $scope, int $ttlSeconds = 60): string;
}

// src/Service/SuperAdminClient.php — wrapper HTTP autour d'un clone
final readonly class SuperAdminClient {
    public function health(ManagedSite $site): HealthResponse;
    public function statsSummary(ManagedSite $site, DateTimeImmutable $from, DateTimeImmutable $to): StatsSummary;
    public function statsTimeseries(ManagedSite $site, ...): TimeseriesPayload;
    public function listAdmins(ManagedSite $site): array;
    public function createAdmin(ManagedSite $site, string $email): CreatedAdmin;
    public function disableAdmin(ManagedSite $site, int $adminId): array;
    public function enableAdmin(ManagedSite $site, int $adminId): array;
}

// src/Service/HealthMonitor.php — cron périodique
// Loop sur chaque site activé, appelle ->health(), persiste un SiteHealthCheck.

// src/Service/StatsSyncService.php — cron quotidien
// Pull les stats de la veille pour chaque site, persist en SiteDailyStats.
```

### 7.3 Routes

```
/                          → home (marketing)
/services                  → marketing
/contact                   → marketing + formulaire
/admin/login               → auth central
/admin                     → redirect /admin/sites
/admin/sites               → liste des sites + statut santé
/admin/sites/new           → ajouter un site (domain, label, fingerprint)
/admin/sites/{id}          → dashboard d'un site (stats + admins)
/admin/sites/{id}/admins   → CRUD admins du clone via API super-admin
/admin/aggregated          → vue agrégée tous sites (top paths globaux, etc.)
```

### 7.4 Cron

Deux jobs (Symfony Messenger + scheduler ou cron OS direct) :

- **Toutes les 5 min** : `HealthMonitor::checkAll()` — ping `/health` de
  chaque site activé, stocke résultat. Alerte (email/Sentry) si 3 checks
  successifs KO.
- **Une fois par jour à 04h00** : `StatsSyncService::syncYesterday()` —
  pull les stats de la veille pour chaque site.

Considère `symfony/scheduler` (livre out-of-the-box avec Messenger).

---

## 8. Sprint 0 — Bootstrap (1 jour)

1. `composer create-project symfony/skeleton:7.* central` (ou clone le
   repo existant si skeleton déjà initialisé).
2. `composer require symfony/security-bundle doctrine/orm doctrine/doctrine-migrations-bundle web-token/jwt-framework symfony/http-client paragonie/sodium_compat`.
3. `composer require --dev phpunit/phpunit symfony/browser-kit dama/doctrine-test-bundle`.
4. Frontend : `npm create vite@latest assets -- --template vue-ts`,
   ajuster avec `pentatrion/vite-bundle` (idem WebBase) et copier
   `assets/design-system/index.scss` depuis WebBase comme base.
5. Génère la paire Ed25519 (cf. §6.1). Stocke privée hors-git.
6. CI GitHub Actions équivalent à WebBase (phpunit + vitest + build).

## 9. Sprint 1 — MVP fonctionnel (3-5 jours)

1. Entités `CentralAdmin`, `ManagedSite`, `SiteHealthCheck`,
   `SiteDailyStats` + migrations.
2. Auth central : `json_login`, store Pinia `auth`, page login (réutilise
   le pattern WebBase).
3. `JwtSigner` + tests unitaires (vérifie iss/aud/exp/jti/scope).
4. `SuperAdminClient` + tests via `MockHttpClient`.
5. Page `/admin/sites` : liste + ajout. Champ fingerprint vérifié contre
   la clé publique demandée à l'admin du clone.
6. Page `/admin/sites/{id}` : appelle `/health` direct au mount,
   affiche le résultat live.
7. Cron `HealthMonitor` toutes les 5 min.

## 10. Sprint 2 — Stats + admins (3-5 jours)

1. `StatsSyncService` cron quotidien.
2. Page `/admin/sites/{id}` : graphique timeseries page_views (30j),
   top paths.
3. Page `/admin/sites/{id}/admins` : list + create + disable/enable via
   `SuperAdminClient`.
4. Page `/admin/aggregated` : somme des page_views tous sites + top 20
   paths globaux.
5. Alerte email quand 3 checks `/health` consécutifs KO.

## 11. Sprint 3 — Site marketing (2-3 jours)

1. Pages publiques `home`, `services`, `contact` — peuvent être un mini
   CMS façon WebBase ou des templates Twig statiques (au choix).
2. Formulaire de contact (rate limit + honeypot, comme WebBase).
3. SEO de base (sitemap, robots.txt, OG).

---

## 12. Pièges connus

- **Le `aud` doit matcher EXACTEMENT le `Host` HTTP** que le clone reçoit.
  Si le clone est derrière Cloudflare et qu'on tape `https://client.fr`,
  le `Host:` reçu par le PHP backend est probablement `client.fr` (sans
  schéma, sans port). Pour les sous-domaines : tester en local d'abord.
- **L'horloge du central doit être proche de celle des clones** (NTP).
  Tolérance par défaut 30s, mais si une VM dérive, on tombe sur
  `expired` ou `not_yet_valid`.
- **`jti` doit être unique** (UUID v4 ou `bin2hex(random_bytes(16))`).
  Réutiliser le même → 401 `replayed` dès le 2e appel.
- **Le clone répond `404` quand `super_admin.enabled: false`**, pas
  `401`/`403`. Distinguer dans le central « site inconnu côté clone »
  vs « clone vraiment down ».
- **Le mot de passe temporaire renvoyé par `POST /admins`** n'est visible
  qu'une fois. Le central doit l'afficher à l'admin (moi) qui l'envoie au
  client en canal sûr (Signal, mot de passe partagé). Ne JAMAIS le logger.
- **`paragonie/sodium_compat`** est utile pour les serveurs sans libsodium
  natif, mais préfère `ext-sodium` si dispo (PHP 8.4 l'inclut souvent).

---

## 13. Tests à écrire (objectifs minimum)

- `JwtSignerTest` : sign + decode self → vérifie tous les claims présents,
  rejette une clé invalide, TTL respecté.
- `SuperAdminClientTest` (avec `MockHttpClient`) : sérialisation request,
  parsing réponses, erreurs 401/403/404 retournent des résultats typés.
- `HealthMonitorTest` : 1 site OK + 1 site KO → état persisté correct.
- `ManagedSiteAdminControllerTest` : auth requise, CRUD persiste.
- E2E Playwright : login central → ajout d'un site → check `/health` OK.

---

## 14. Référence : où regarder côté WebBase

Si tu veux confirmer une signature, va lire ces fichiers (ils ne changent
pas par sprint) :

| Fichier WebBase | Ce qu'il contient |
|---|---|
| `src/Security/SuperAdmin/JwtVerifier.php` | Vérif côté clone — règles `iss`/`aud`/`exp`/`jti`/`scope` |
| `src/Security/SuperAdmin/SuperAdminTokenAuthenticator.php` | Authenticator Symfony qui consomme le `Bearer` |
| `src/Security/SuperAdmin/SuperAdminPrincipal.php` | L'objet "user" qui porte les scopes |
| `src/Controller/SuperAdminApi/HealthController.php` | Body de réponse exact pour `/health` |
| `src/Controller/SuperAdminApi/StatsController.php` | Body pour `/stats/summary` et `/stats/timeseries` |
| `src/Controller/SuperAdminApi/AccessController.php` | Body pour `/access/admins` (CRUD) |
| `config/super_admin.yaml` | Config clone côté contrat |
| `tests/fixtures/super_admin/generate_test_keys.php` | Script de génération de clés Ed25519 — mêmes paramètres à utiliser côté central |
| `tests/Support/JwtTestKit.php` | Pattern de signature/forge JWT pour les tests |
| `docs/SUPER_ADMIN_API.md` | Doc complète du contrat |

---

## 15. Définition de "fait" pour chaque sprint

| Sprint | Done quand |
|---|---|
| 0 | `composer install && npm run build && php bin/phpunit` passe sur un repo vide |
| 1 | Je peux ajouter le site `exemple.rivierematthieu.com` au registre depuis `/admin/sites` et voir son `/health` répondre OK live |
| 2 | Je vois le graphique des page_views de la semaine pour le showcase, et je peux désactiver/réactiver un admin du showcase depuis l'UI centrale |
| 3 | `www.rivierematthieu.com` sert mon site personnel (home, services, contact fonctionnel) |

---

## Ce que j'attends de toi pour démarrer

1. Commence par lire ce brief en entier.
2. Confirme la stack que tu prends (Symfony+Vue identique à WebBase, ou
   propose une alternative argumentée).
3. Propose un plan détaillé pour le Sprint 0 (init + crypto setup).
4. Une fois que je valide le plan, attaque Sprint 1.

Pas la peine de me redemander des précisions sur le contrat API — il
est figé côté clone et documenté ci-dessus. Si un point reste flou,
réfère-toi aux fichiers listés en §14 (j'ai accès au repo WebBase et
je peux te coller leur contenu si tu le demandes).
