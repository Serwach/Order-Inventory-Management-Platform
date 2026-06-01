#!/usr/bin/env bash
# restart.sh — Start the OIMP Symfony stack and wait until the API is reachable.
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_DIR="$(dirname "$SCRIPT_DIR")"

# ─── Colours ──────────────────────────────────────────────────────────────────
RED='\033[0;31m'; YELLOW='\033[1;33m'; GREEN='\033[0;32m'; CYAN='\033[0;36m'; NC='\033[0m'
info()    { echo -e "${CYAN}[OIMP]${NC} $*"; }
warn()    { echo -e "${YELLOW}[WARN]${NC} $*"; }
success() { echo -e "${GREEN}[OK]${NC}   $*"; }
error()   { echo -e "${RED}[ERR]${NC}  $*"; }

# ─── Force Docker to use the working socket ───────────────────────────────────
# The desktop-linux context (Docker Desktop) may not be running.
# Pick the first context whose daemon actually responds.
pick_docker_context() {
    local contexts
    contexts=$(docker context ls --format '{{.Name}}' 2>/dev/null || echo "default")

    while IFS= read -r ctx; do
        if docker --context "$ctx" info &>/dev/null 2>&1; then
            echo "$ctx"
            return 0
        fi
    done <<< "$contexts"

    echo "default"   # last resort
}

DOCKER_CTX=$(pick_docker_context)
export DOCKER_CONTEXT="$DOCKER_CTX"
info "Using Docker context: ${DOCKER_CTX}"

# ─── Ports used by this project ───────────────────────────────────────────────
OIMP_PORTS=(8080 5432 6379 5672 15672 9200 1025 8025)
NGINX_PORT=${NGINX_PORT:-8080}
API_URL="http://localhost:${NGINX_PORT}/api"

# ─── Step 1: Stop current OIMP stack ──────────────────────────────────────────
cd "$PROJECT_DIR"

info "Stopping existing OIMP services…"
docker compose down --remove-orphans 2>/dev/null || true

# ─── Step 2: Stop other Docker containers that hold our ports ─────────────────
info "Checking for port conflicts from other containers…"
running=$(docker ps --format '{{.Names}}\t{{.Ports}}' 2>/dev/null || true)

if [[ -n "$running" ]]; then
    to_stop=()
    while IFS=$'\t' read -r name ports; do
        for port in "${OIMP_PORTS[@]}"; do
            if echo "$ports" | grep -qE "(0\.0\.0\.0|::):${port}->"; then
                to_stop+=("$name")
                break
            fi
        done
    done <<< "$running"

    if [[ ${#to_stop[@]} -gt 0 ]]; then
        info "Stopping conflicting container(s): ${to_stop[*]}"
        docker stop "${to_stop[@]}" &>/dev/null || true
    fi
fi

# ─── Step 3: Kill non-Docker processes still holding ports ────────────────────
sleep 1
still_busy=()
for port in "${OIMP_PORTS[@]}"; do
    if ss -tlnp 2>/dev/null | grep -qE " [^ ]*:${port} "; then
        still_busy+=("$port")
    fi
done

if [[ ${#still_busy[@]} -gt 0 ]]; then
    warn "Port(s) still busy (non-Docker): ${still_busy[*]} — trying fuser -k…"
    for port in "${still_busy[@]}"; do
        sudo fuser -k "${port}/tcp" &>/dev/null 2>&1 || true
    done
    sleep 1
fi

# Hard-fail if critical port 8080 is still blocked
if ss -tlnp 2>/dev/null | grep -qE " [^ ]*:8080 "; then
    error "Port 8080 is still in use. Cannot start nginx."
    error "Run: sudo ss -tlnp | grep 8080"
    exit 1
fi

# ─── Step 4: Ensure runtime directories exist (volume mount needs them) ───────
mkdir -p \
    "$PROJECT_DIR/var/cache/dev" \
    "$PROJECT_DIR/var/cache/prod" \
    "$PROJECT_DIR/var/log"

# ─── Step 5: Start the stack ──────────────────────────────────────────────────
echo ""
info "Starting OIMP stack…"
docker compose up -d --remove-orphans

# ─── Step 6: Wait for php-fpm to be ready ─────────────────────────────────────
info "Waiting for php-fpm…"
for i in $(seq 1 60); do
    if docker compose logs php 2>/dev/null | grep -q "ready to handle connections"; then
        break
    fi
    if docker compose logs php 2>/dev/null | grep -q "Fatal error\|Uncaught"; then
        error "php-fpm failed to start. Showing last logs:"
        docker compose logs php --tail=20
        exit 1
    fi
    sleep 2
done

# ─── Step 7: Refresh nginx DNS (php container may have new IP after restart) ──
info "Restarting nginx to refresh upstream DNS…"
docker compose restart nginx &>/dev/null

# ─── Step 8: Run migrations ───────────────────────────────────────────────────
info "Running database migrations…"
docker compose exec -T php bin/console doctrine:migrations:migrate \
    --no-interaction --allow-no-migration 2>&1 \
    | grep -v "^\[notice\] Migrating\|^\[notice\] finished" || true

# ─── Step 9: Wait for HTTP 200/401 on the API endpoint ───────────────────────
info "Waiting for API to respond at ${API_URL}…"
for i in $(seq 1 60); do
    http_code=$(curl -s -o /dev/null -w "%{http_code}" "${API_URL}" 2>/dev/null || echo "000")
    if [[ "$http_code" =~ ^(200|401|403|404)$ ]]; then
        success "API is up (HTTP ${http_code})."
        break
    fi
    if [[ $i -eq 60 ]]; then
        error "API did not respond after 120s. Last HTTP code: ${http_code}"
        docker compose logs php --tail=30
        exit 1
    fi
    sleep 2
done

# ─── Done ─────────────────────────────────────────────────────────────────────
echo ""
success "Stack is ready."
echo ""
echo "  API           → http://localhost:${NGINX_PORT}/api"
echo "  API Docs      → http://localhost:${NGINX_PORT}/api/docs"
echo "  RabbitMQ UI   → http://localhost:${RABBITMQ_MANAGEMENT_PORT:-15672}  (guest/guest)"
echo "  MailHog       → http://localhost:${MAILHOG_UI_PORT:-8025}"
echo "  OpenSearch    → http://localhost:${OPENSEARCH_PORT:-9200}"
echo ""
echo "  Logs:  docker compose logs -f"
echo "  Shell: docker compose exec php sh"
