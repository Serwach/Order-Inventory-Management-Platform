#!/usr/bin/env bash
# reset-db.sh — Drop, recreate, migrate, and seed the development database.
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$(dirname "$SCRIPT_DIR")"

CYAN='\033[0;36m'; GREEN='\033[0;32m'; NC='\033[0m'
info()    { echo -e "${CYAN}[OIMP]${NC} $*"; }
success() { echo -e "${GREEN}[OK]${NC}   $*"; }

PHP="docker compose exec -T php"

info "Dropping database…"
$PHP php bin/console doctrine:database:drop --force --if-exists

info "Creating database…"
$PHP php bin/console doctrine:database:create --if-not-exists

info "Running migrations…"
$PHP php bin/console doctrine:migrations:migrate --no-interaction

info "Loading fixtures…"
$PHP php bin/console doctrine:fixtures:load --no-interaction

success "Database reset complete."
echo "  Demo login: owner@acme-corp.dev / password123"
