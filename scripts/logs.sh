#!/usr/bin/env bash
# logs.sh — Tail logs for one or all services.
# Usage: ./scripts/logs.sh [service]   e.g. ./scripts/logs.sh worker
set -euo pipefail

cd "$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)/.."

SERVICE="${1:-}"

if [[ -n "$SERVICE" ]]; then
    docker compose logs -f --tail=100 "$SERVICE"
else
    docker compose logs -f --tail=50
fi
