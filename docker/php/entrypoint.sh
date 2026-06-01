#!/bin/sh
# Startup script for both php-fpm and worker containers.
# The host volume mount (.:/var/www/html) overwrites everything the image built,
# so vendor, JWT keys and runtime dirs must be set up here at container start.
set -e

APP_DIR=/var/www/html
cd "$APP_DIR"

# ── 1. Vendor install ────────────────────────────────────────────────────────
# Must use --no-scripts: post-install scripts (cache:clear, assets:install)
# need a fully booted kernel that isn't available yet at this stage.
if [ ! -f vendor/autoload.php ]; then
    echo "[entrypoint] vendor/ missing — running composer install..."
    composer install \
        --no-interaction \
        --prefer-dist \
        --no-progress \
        --no-scripts
fi

# ── 2. Runtime directories ───────────────────────────────────────────────────
mkdir -p var/cache var/log

# ── 3. JWT keypair ───────────────────────────────────────────────────────────
# Generated with openssl to avoid needing a working kernel (avoids
# chicken-and-egg: kernel can't boot without keys, keys need kernel to generate).
# Only the php-fpm container generates them; worker reads the same files via volume.
if [ "$1" = "php-fpm" ]; then
    mkdir -p config/jwt
    _pass="${JWT_PASSPHRASE:-change_me_jwt_passphrase}"
    if [ ! -f config/jwt/private.pem ]; then
        echo "[entrypoint] JWT keys missing — generating keypair with openssl..."
        openssl genrsa -aes256 \
            -passout "pass:${_pass}" \
            -out config/jwt/private.pem 4096 2>/dev/null
        openssl rsa \
            -passin "pass:${_pass}" \
            -in  config/jwt/private.pem \
            -pubout \
            -out config/jwt/public.pem 2>/dev/null
        echo "[entrypoint] JWT keypair generated."
    fi
    # Ensure www-data (php-fpm process) can read the keys regardless of host uid
    chmod 640 config/jwt/private.pem
    chmod 644 config/jwt/public.pem
    chown root:www-data config/jwt/private.pem config/jwt/public.pem 2>/dev/null || \
        chmod 644 config/jwt/private.pem
fi

# ── Note: cache:clear and migrations are intentionally omitted ───────────────
# Symfony dev mode rebuilds the container cache automatically on first request.
# Run migrations explicitly: make db-migrate

exec docker-php-entrypoint "$@"
