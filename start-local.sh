#!/usr/bin/env bash
#
# start-local.sh
# Compile la SPA Vue (TS + SCSS + traductions) en bundle de production,
# applique les migrations Doctrine et démarre le serveur Symfony local
# en mode `prod` (les assets buildés sont servis tels quels).
#
# Usage :
#   ./start-local.sh                   # cycle normal (build + migrate + serve)
#   ./start-local.sh --reset-db        # ⚠ purge et recharge les fixtures
#   ./start-local.sh --port=8765       # port custom (défaut : 8000)
#   ./start-local.sh --help
#
# Pré-requis :
#   • PHP 8.3+, Composer 2.x
#   • Node 22.x, npm
#   • MariaDB lancée + DATABASE_URL valide dans .env.local
#

set -euo pipefail
cd "$(dirname "$0")"

RESET_DB=0
PORT=8000

for arg in "$@"; do
  case "$arg" in
    --reset-db)   RESET_DB=1 ;;
    --port=*)     PORT="${arg#--port=}" ;;
    -h|--help)
      sed -n '2,18p' "$0" | sed 's/^# \{0,1\}//'
      exit 0
      ;;
    *)
      echo "Argument inconnu : $arg (essaie --help)"
      exit 1
      ;;
  esac
done

step()    { printf "\n\033[1;36m▸ %s\033[0m\n" "$1"; }
warn()    { printf "\033[1;33m  ⚠ %s\033[0m\n" "$1"; }
ok()      { printf "\033[1;32m  ✓ %s\033[0m\n" "$1"; }

if [ ! -f .env.local ]; then
  warn ".env.local introuvable — copie depuis .env.example puis renseigne les vraies valeurs."
  exit 1
fi

step "Dépendances PHP"
if [ ! -d vendor ]; then
  composer install --no-interaction --no-progress
else
  ok "vendor/ déjà présent"
fi

step "Dépendances JS"
if [ ! -d node_modules ]; then
  npm ci --no-fund --no-audit
else
  ok "node_modules/ déjà présent"
fi

step "Migrations Doctrine"
php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration

if [ "$RESET_DB" -eq 1 ]; then
  step "Rechargement des fixtures (purge totale ⚠)"
  php bin/console doctrine:fixtures:load --no-interaction
else
  ok "Fixtures non touchées (utilise --reset-db pour purger et recharger)"
fi

step "Build prod de la SPA Vue (TypeScript + SCSS + locales fr/en bundlées)"
npm run build

step "Cache Symfony prod"
APP_ENV=prod APP_DEBUG=0 php bin/console cache:clear --no-warmup
APP_ENV=prod APP_DEBUG=0 php bin/console cache:warmup

step "Lancement du serveur Symfony sur http://localhost:${PORT}"
echo "  → /fr        (accueil français)"
echo "  → /en        (accueil anglais)"
echo "  → /admin/login  (back-office)"
echo "  → Ctrl+C pour arrêter"
echo

exec env APP_ENV=prod APP_DEBUG=0 symfony server:start --no-tls --port="${PORT}"
