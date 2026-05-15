#!/usr/bin/env bash
# Deploy rivierematthieu.com to Hostinger via SSH + rsync.
#
# Hostinger Business plans expose SSH; we don't use Docker because the host is
# a shared LAMP environment. The script:
#   1. Builds the front-end locally (npm run build → public/build/)
#   2. Rsyncs the source (excluding vendor/, node_modules/, caches, .git, .env*) to remote
#   3. Runs `composer install --no-dev` on the remote (uses the server's PHP 8.4)
#   4. Runs migrations + clears the prod cache + appends a release log line
#
# Usage:
#   ./deploy.sh                         # full deploy (build + sync + post-deploy)
#   ./deploy.sh --no-build              # skip the front-end build (public/build/ already up-to-date)
#   ./deploy.sh --no-composer           # skip remote composer install (composer.lock unchanged)
#   ./deploy.sh --dry-run               # rsync --dry-run, no remote commands
#   ./deploy.sh --skip-migrations       # don't run doctrine:migrations:migrate
#   ./deploy.sh --prune-stale           # remove dev-only files left on the remote by older deploys
#   ./deploy.sh --release-tag=v1.2.3    # tag the deploy in the remote release log
#
# Required env vars (put them in .deploy.env, gitignored):
#   HOSTINGER_SSH_USER      - e.g. u123456789
#   HOSTINGER_SSH_HOST      - e.g. rivierematthieu.com or 1.2.3.4
#   HOSTINGER_SSH_PORT      - usually 65002 on Hostinger
#   HOSTINGER_REMOTE_PATH   - e.g. /home/u123456789/domains/rivierematthieu.com/public_html
#   HOSTINGER_PHP_BIN       - optional, defaults to /usr/bin/php8.4

set -euo pipefail

cd "$(dirname "$0")"

# ─── Load .deploy.env if present ─────────────────────────────────────────────
if [ -f .deploy.env ]; then
  set -a
  # shellcheck disable=SC1091
  source .deploy.env
  set +a
fi

DO_BUILD=1
DO_COMPOSER=1
DRY_RUN=0
RUN_MIGRATIONS=1
PRUNE_STALE=0
RELEASE_TAG="$(git rev-parse --short HEAD 2>/dev/null || echo 'manual')"

for arg in "$@"; do
  case "$arg" in
    --no-build)         DO_BUILD=0 ;;
    --no-composer)      DO_COMPOSER=0 ;;
    --dry-run)          DRY_RUN=1 ;;
    --skip-migrations)  RUN_MIGRATIONS=0 ;;
    --prune-stale)      PRUNE_STALE=1 ;;
    --release-tag=*)    RELEASE_TAG="${arg#*=}" ;;
    *) echo "Unknown flag: $arg" >&2; exit 64 ;;
  esac
done

: "${HOSTINGER_SSH_USER:?Set HOSTINGER_SSH_USER in .deploy.env}"
: "${HOSTINGER_SSH_HOST:?Set HOSTINGER_SSH_HOST in .deploy.env}"
: "${HOSTINGER_SSH_PORT:=65002}"
: "${HOSTINGER_REMOTE_PATH:?Set HOSTINGER_REMOTE_PATH in .deploy.env}"
: "${HOSTINGER_PHP_BIN:=/usr/bin/php8.4}"

REMOTE="${HOSTINGER_SSH_USER}@${HOSTINGER_SSH_HOST}"
SSH="ssh -p ${HOSTINGER_SSH_PORT} ${REMOTE}"

# ─── Local build + pre-flight ────────────────────────────────────────────────
if [ "$DO_BUILD" -eq 1 ]; then
  echo "▶ Building front-end (npm run build)…"
  npm run build

  echo "▶ Pre-flight: composer validate + audit"
  composer validate --no-check-publish --strict || { echo "Aborting: composer.json / composer.lock out of sync." >&2; exit 65; }
  composer audit --no-dev || { echo "Aborting deploy: composer audit reported issues." >&2; exit 65; }
fi

# ─── Working tree sanity ─────────────────────────────────────────────────────
if ! git diff --quiet 2>/dev/null; then
  echo "⚠️  Working tree has uncommitted changes. Continuing anyway, but the deploy"
  echo "   will include those files. Press Ctrl-C in 3 s to abort, or wait."
  sleep 3
fi

# ─── Rsync ───────────────────────────────────────────────────────────────────
RSYNC_FLAGS=(
  -avz
  --delete
  --human-readable
  -e "ssh -p ${HOSTINGER_SSH_PORT}"

  # Patterns starting with `/` are anchored to the transfer root, so they cannot
  # accidentally match a same-named dir/file deeper in the tree. Critical here:
  # `/assets/` excludes the Vue/TS sources only — without the leading slash,
  # rsync would *also* skip `public/build/assets/` (the compiled JS/CSS the
  # browser loads) and break the site.

  # VCS / IDE / local tooling — never useful on the server.
  --exclude='/.git/'
  --exclude='/.github/'
  --exclude='/.gitignore'
  --exclude='/.idea/'
  --exclude='/.claude/'

  # Dependencies — installed on the remote by composer (PHP) ; npm is build-only.
  --exclude='node_modules/'         # keep unanchored: also exclude nested ones
  --exclude='/vendor/'
  --exclude='/package.json'
  --exclude='/package-lock.json'

  # Front-end sources — already compiled into public/build/ locally.
  --exclude='/assets/'
  --exclude='/vite.config.ts'
  --exclude='/tsconfig.json'
  --exclude='/tsconfig.tsbuildinfo'

  # Tests + testing config — not used at runtime.
  --exclude='/tests/'
  --exclude='/test-results/'
  --exclude='/.phpunit.cache/'
  --exclude='/phpunit.dist.xml'
  --exclude='/playwright.config.ts'
  --exclude='/.env.test'

  # Runtime state that must stay local to each environment.
  --exclude='/var/cache/*'
  --exclude='/var/log/*'
  --exclude='/var/test.db'
  --exclude='/.env.local'
  --exclude='/.env.*.local'

  # Deploy + local-dev tooling — used from the dev machine, not the server.
  --exclude='/deploy.sh'
  --exclude='/.deploy.env'
  --exclude='/.deploy.env.example'
  --exclude='/start-local.sh'

  # Project docs — kept in git, not needed on the host.
  --exclude='/docs/'
  --exclude='/CLAUDE.md'
  --exclude='/README.md'
  --exclude='/*.docx'

  # Misc.
  --exclude='/public/build/.vite/manifest.json.gz'
  --include='/public/uploads/'
  --exclude='/public/uploads/*/*'  # keep dir, skip user-uploaded content
  --exclude='*.log'                # any depth — runtime logs everywhere
)

if [ "$DRY_RUN" -eq 1 ]; then
  RSYNC_FLAGS+=(--dry-run)
fi

echo "▶ Rsyncing to ${REMOTE}:${HOSTINGER_REMOTE_PATH}…"
rsync "${RSYNC_FLAGS[@]}" ./ "${REMOTE}:${HOSTINGER_REMOTE_PATH}/"

if [ "$DRY_RUN" -eq 1 ]; then
  echo "✓ Dry-run complete (no remote commands executed)."
  exit 0
fi

# ─── Post-deploy on remote ───────────────────────────────────────────────────
echo "▶ Post-deploy commands on remote…"
$SSH bash -lc "'
set -euo pipefail
cd ${HOSTINGER_REMOTE_PATH}

# .env.local must exist on the remote — never copied from local.
if [ ! -f .env.local ]; then
  echo \"⚠️  .env.local missing on remote — populate it (see docs/DEPLOY.md §3) before clients hit the site.\" >&2
fi

# Prune dev-only files that earlier deploys may have shipped. Explicit allow-list
# of paths only — never globs — so we cannot wipe vendor/, var/cache, uploads,
# .env.local or super-admin keys. Safe to re-run; missing paths are skipped.
if [ \"${PRUNE_STALE}\" -eq 1 ]; then
  echo \"▶ Pruning stale dev-only files on remote…\"
  for path in \\
    assets docs tests test-results node_modules \\
    .github .claude .idea .phpunit.cache \\
    CLAUDE.md README.md devis_site_web.docx \\
    package.json package-lock.json \\
    vite.config.ts tsconfig.json tsconfig.tsbuildinfo \\
    phpunit.dist.xml playwright.config.ts \\
    start-local.sh deploy.sh .deploy.env.example \\
    .env.test .gitignore; do
    if [ -e \"\$path\" ]; then
      echo \"  - rm -rf \$path\"
      rm -rf -- \"\$path\"
    fi
  done
fi

# Install vendor for prod on the remote — uses the server'\''s PHP 8.4 so the
# autoloader and any platform checks match the runtime exactly.
# APP_ENV=prod is forced so the post-install cache:clear runs in prod and does
# not try to load dev-only bundles (DoctrineFixturesBundle, MakerBundle, …).
if [ \"${DO_COMPOSER}\" -eq 1 ]; then
  APP_ENV=prod APP_DEBUG=0 composer install --no-dev --optimize-autoloader \
    --classmap-authoritative --no-interaction --no-progress --prefer-dist
fi

# Generate the Ed25519 keypair if it is missing (super-admin dashboard).
if [ ! -f var/keys/super_admin_private.pem ]; then
  mkdir -p var/keys
  chmod 700 var/keys
  openssl genpkey -algorithm Ed25519 -out var/keys/super_admin_private.pem
  openssl pkey -in var/keys/super_admin_private.pem -pubout -out var/keys/super_admin_public.pem
  chmod 600 var/keys/*.pem
  echo \"✓ Generated Ed25519 keypair (super-admin)\"
fi

# Drop the prod cache so the next request rebuilds against the new code.
${HOSTINGER_PHP_BIN} bin/console cache:clear --env=prod --no-debug

if [ \"${RUN_MIGRATIONS}\" -eq 1 ]; then
  ${HOSTINGER_PHP_BIN} bin/console doctrine:migrations:migrate --no-interaction --env=prod
fi

# Append a line to the release log so we can tell what shipped, when.
echo \"\$(date -u +%Y-%m-%dT%H:%M:%SZ)  ${RELEASE_TAG}\" >> var/log/releases.log
'"

echo "✓ Deploy complete (${RELEASE_TAG})"
echo "  Smoke-test https://${HOSTINGER_SSH_HOST}/ and check the release log:"
echo "  ssh -p ${HOSTINGER_SSH_PORT} ${REMOTE} 'tail -5 ${HOSTINGER_REMOTE_PATH}/var/log/releases.log'"
