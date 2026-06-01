.PHONY: help build up down restart logs shell \
        composer-install composer-update \
        db-create db-migrate db-reset db-fixtures \
        jwt-generate \
        test test-unit test-integration test-coverage \
        lint phpstan rector cs-fix \
        cache-clear search-index \
        worker-start worker-stop

# ─── COLORS ─────────────────────────────────────────────────────────────────
GREEN  := $(shell tput -Txterm setaf 2)
YELLOW := $(shell tput -Txterm setaf 3)
RESET  := $(shell tput -Txterm sgr0)

## ─── HELP ───────────────────────────────────────────────────────────────────
help: ## Show this help
	@echo ''
	@echo '  ${GREEN}OIMP Platform — Development Commands${RESET}'
	@echo ''
	@awk 'BEGIN {FS = ":.*?## "} /^[a-zA-Z_-]+:.*?## / {printf "  ${YELLOW}%-25s${RESET} %s\n", $$1, $$2}' $(MAKEFILE_LIST)
	@echo ''

## ─── DOCKER ─────────────────────────────────────────────────────────────────
build: ## Build Docker images
	docker compose build --no-cache

up: ## Start all services
	docker compose up -d

down: ## Stop all services
	docker compose down

restart: down up ## Restart all services

logs: ## Follow container logs (usage: make logs s=php)
	docker compose logs -f $(s)

shell: ## Open shell in PHP container
	docker compose exec php sh

## ─── SETUP ───────────────────────────────────────────────────────────────────
setup: up composer-install jwt-generate db-create db-migrate db-fixtures search-index ## Full local environment setup
	@echo "${GREEN}✓ Environment ready. Open http://localhost:8080${RESET}"

composer-install: ## Install PHP dependencies
	docker compose exec php composer install

composer-update: ## Update PHP dependencies
	docker compose exec php composer update

## ─── JWT ─────────────────────────────────────────────────────────────────────
jwt-generate: ## Generate JWT key pair
	mkdir -p config/jwt
	docker compose exec php bin/console lexik:jwt:generate-keypair --overwrite

## ─── DATABASE ────────────────────────────────────────────────────────────────
db-create: ## Create database
	docker compose exec php bin/console doctrine:database:create --if-not-exists

db-migrate: ## Run migrations
	docker compose exec php bin/console doctrine:migrations:migrate --no-interaction

db-diff: ## Generate migration from entity changes
	docker compose exec php bin/console doctrine:migrations:diff

db-reset: ## Drop, recreate and migrate database
	docker compose exec php bin/console doctrine:database:drop --if-exists --force
	docker compose exec php bin/console doctrine:database:create
	docker compose exec php bin/console doctrine:migrations:migrate --no-interaction

db-fixtures: ## Load fixtures
	docker compose exec php bin/console doctrine:fixtures:load --no-interaction

db-validate: ## Validate Doctrine mapping
	docker compose exec php bin/console doctrine:schema:validate

## ─── SEARCH ──────────────────────────────────────────────────────────────────
search-index: ## Create/update OpenSearch indices
	docker compose exec php bin/console app:search:reindex --all

## ─── TESTS ───────────────────────────────────────────────────────────────────
test: ## Run all tests
	docker compose exec php bin/phpunit

test-unit: ## Run unit tests only
	docker compose exec php bin/phpunit --testsuite Unit

test-integration: ## Run integration tests only
	docker compose exec php bin/phpunit --testsuite Integration

test-coverage: ## Run tests with HTML coverage report
	docker compose exec php bin/phpunit --coverage-html coverage/html --coverage-clover coverage/clover.xml
	@echo "${GREEN}Coverage report: coverage/html/index.html${RESET}"

## ─── CODE QUALITY ────────────────────────────────────────────────────────────
lint: ## Run all linters
	docker compose exec php bin/console lint:yaml config
	docker compose exec php bin/console lint:twig templates
	docker compose exec php bin/console lint:container

phpstan: ## Run PHPStan static analysis
	docker compose exec php vendor/bin/phpstan analyse --memory-limit=512M

rector: ## Run Rector code upgrade (dry-run)
	docker compose exec php vendor/bin/rector process --dry-run

rector-fix: ## Run Rector code upgrade (apply)
	docker compose exec php vendor/bin/rector process

cs-check: ## Check coding standards
	docker compose exec php vendor/bin/ecs check

cs-fix: ## Fix coding standards
	docker compose exec php vendor/bin/ecs check --fix

quality: lint phpstan cs-check ## Run all quality checks

## ─── CACHE ────────────────────────────────────────────────────────────────────
cache-clear: ## Clear application cache
	docker compose exec php bin/console cache:clear

cache-warmup: ## Warm up application cache
	docker compose exec php bin/console cache:warmup

## ─── MESSENGER ───────────────────────────────────────────────────────────────
worker-start: ## Start message workers (foreground)
	docker compose exec php bin/console messenger:consume async async_priority_high --time-limit=3600 -vv

failed-retry: ## Retry failed messages
	docker compose exec php bin/console messenger:failed:retry --all -vv

failed-show: ## Show failed messages
	docker compose exec php bin/console messenger:failed:show -vv

## ─── UTILITIES ───────────────────────────────────────────────────────────────
routes: ## List all routes
	docker compose exec php bin/console debug:router

services: ## List all DI services
	docker compose exec php bin/console debug:container

env: ## Show current environment config
	docker compose exec php bin/console debug:dotenv

ps: ## Show running services
	docker compose ps
