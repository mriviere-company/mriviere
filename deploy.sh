#!/usr/bin/env bash
# Deploy rivierematthieu.com to Hostinger via SSH + rsync.
#
# Hostinger Business plans expose SSH; we don't use Docker because the host is
# a shared LAMP environment. The script:
#   1. Builds the front-end and installs vendor for prod (no-dev, optimised autoloader)
#   2. Rsyncs the project (excluding caches, node_modules, .git, .env*) to the remote
#   3. Runs migrations + clears the prod cache + restarts php-fpm pool (best-effort)
#
# Usage:
#   ./deploy.sh                         # full deploy (build + sync + post-deploy)
#   ./deploy.sh --no-build              # skip the build step (you already have public/build/)
#   ./deploy.sh --dry-run               # rsync --dry-run, no remote commands
#   ./deploy.sh --skip-migrations       # don't run doctrine:migrations:migrate
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
DRY_RUN=0
RUN_MIGRATIONS=1
RELEASE_TAG="$(git rev-parse --short HEAD 2>/dev/null || echo 'manual')"

for arg in "$@"; do
  case "$arg" in
    --no-build)         DO_BUILD=0 ;;
    --dry-run)          DRY_RUN=1 ;;
    --skip-migrations)  RUN_MIGRATIONS=0 ;;
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

# ─── Local build ─────────────────────────────────────────────────────────────
if [ "$DO_BUILD" -eq 1 ]; then
  echo "▶ Building front-end (npm run build)…"
  npm run build

  echo "▶ Installing vendor for prod (no-dev, optimised autoloader)…"
  composer install --no-dev --optimize-autoloader --classmap-authoritative --no-interaction

  echo "▶ Pre-flight: composer audit"
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
  --exclude='.git/'
  --exclude='node_modules/'
  --exclude='var/cache/*'
  --exclude='var/log/*'
  --exclude='var/test.db'
  --exclude='.env.local'
  --exclude='.env.*.local'
  --exclude='.deploy.env'
  --exclude='tests/'
  --exclude='public/build/.vite/manifest.json.gz'
  --include='public/uploads/'
  --exclude='public/uploads/*/*'   # keep dir, skip user-uploaded content
  --exclude='.idea/'
  --exclude='*.log'
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
