#!/usr/bin/env bash
# shell.sh — Open a shell inside the PHP container.
# Usage: ./scripts/shell.sh [service]   default: php
set -euo pipefail

cd "$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)/.."

SERVICE="${1:-php}"
docker compose exec "$SERVICE" bash
