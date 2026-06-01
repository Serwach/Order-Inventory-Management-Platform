# OIMP Platform — Order & Inventory Management Platform

> Production-grade SaaS B2B platform built with **Symfony 7.2**, **PHP 8.3**, **PostgreSQL**, **Redis**, **RabbitMQ**, and **OpenSearch**. Designed as a modular monolith following DDD-lite, CQRS, and event-driven principles.

[![CI](https://github.com/mserwach/Order-Inventory-Management-Platform/actions/workflows/ci.yml/badge.svg)](https://github.com/mserwach/Order-Inventory-Management-Platform/actions)
[![PHPStan Level 8](https://img.shields.io/badge/PHPStan-level%208-brightgreen)](phpstan.neon)
[![PHP 8.3](https://img.shields.io/badge/PHP-8.3-blue)](https://php.net)
[![Symfony 7.2](https://img.shields.io/badge/Symfony-7.2-black)](https://symfony.com)

---

## Table of Contents

- [Architecture Overview](#architecture-overview)
- [Bounded Contexts](#bounded-contexts)
- [Technology Stack](#technology-stack)
- [Getting Started](#getting-started)
- [API Reference](#api-reference)
- [Event Flow](#event-flow)
- [Development Guide](#development-guide)
- [Testing](#testing)
- [Deployment](#deployment)
- [Scaling Notes](#scaling-notes)
- [Roadmap](#roadmap)

---

## Architecture Overview

OIMP follows a **modular monolith** architecture with clear domain boundaries. Each module is self-contained with its own Domain, Application, Infrastructure, and UI layers. Modules communicate via domain events (async, through RabbitMQ) or direct service calls within the same request.

```mermaid
graph TB
    Client["API Client"] -->|JWT Bearer| Nginx
    Nginx -->|FastCGI| PHP["PHP-FPM\n(Symfony 7.2)"]
    PHP -->|SQL| Postgres[(PostgreSQL 16)]
    PHP -->|Cache/Session| Redis[(Redis 7.2)]
    PHP -->|Publish Events| RabbitMQ[(RabbitMQ 3.13)]
    RabbitMQ -->|Consume| Worker["Messenger Worker\n(Supervisor)"]
    Worker -->|SQL| Postgres
    Worker -->|Index| OpenSearch[(OpenSearch 2.x)]
    PHP -->|Search| OpenSearch
    PHP -->|Mail| Mailhog[(MailHog)]
```

### Layered Architecture (per Bounded Context)

```
src/
├── Order/
│   ├── Domain/          ← Entities, Value Objects, Events, Repository Interfaces, Domain Services
│   ├── Application/     ← Commands, Queries, Handlers, Event Handlers, DTOs
│   ├── Infrastructure/  ← Doctrine Repositories, External Services
│   └── UI/              ← HTTP Controllers, API Resources
```

### CQRS Flow

```mermaid
sequenceDiagram
    participant Client
    participant Controller
    participant CommandBus
    participant Handler
    participant AggregateRoot
    participant EventBus
    participant AsyncHandler

    Client->>Controller: POST /api/v1/orders
    Controller->>CommandBus: dispatch(PlaceOrderCommand)
    CommandBus->>Handler: __invoke()
    Handler->>AggregateRoot: Order::place()
    AggregateRoot-->>Handler: Order (with domain events)
    Handler->>CommandBus: return Order
    CommandBus->>EventBus: dispatch(OrderCreated)
    EventBus-->>RabbitMQ: publish async
    Controller-->>Client: 201 Created

    Note over RabbitMQ,AsyncHandler: Async (worker process)
    RabbitMQ->>AsyncHandler: consume OrderCreated
    AsyncHandler->>CommandBus: dispatch(ReserveStockCommand)
```

---

## Bounded Contexts

| Context | Responsibility | Key Aggregates |
|---------|---------------|----------------|
| **Organization** | Multi-tenancy, auth, RBAC, invitations | `Organization`, `User`, `Invitation` |
| **Catalog** | Products, variants, categories | `Product`, `ProductVariant` |
| **Inventory** | Stock levels, reservations, movements | `StockEntry`, `StockReservation`, `StockMovement` |
| **Order** | Order lifecycle, payments, shipments | `Order`, `OrderItem` |
| **Notification** | Transactional emails, webhooks | (stateless handlers) |

### Multi-Tenancy

Every database entity is scoped to an `organization_id`. A Doctrine SQL filter (`TenantFilter`) is enabled per-request after JWT authentication and automatically appends `AND organization_id = '...'` to every query, preventing cross-tenant data leakage.

---

## Technology Stack

### Backend
| Component | Version | Purpose |
|-----------|---------|---------|
| PHP | 8.3 | Runtime |
| Symfony | 7.2 | Framework |
| Doctrine ORM | 3.1 | Persistence |
| API Platform | 3.3 | API layer |
| Lexik JWT | 2.21 | Authentication |
| Symfony Messenger | 7.2 | CQRS + async |

### Infrastructure
| Service | Version | Purpose |
|---------|---------|---------|
| PostgreSQL | 16 | Primary database |
| Redis | 7.2 | Cache, session, rate limiter |
| RabbitMQ | 3.13 | Async message broker |
| OpenSearch | 2.14 | Full-text product search |
| Nginx | 1.25 | Reverse proxy |

### Quality
- PHPStan level 8
- EasyCodingStandard
- Rector (automated upgrades)
- PHPUnit 11
- GitHub Actions CI/CD

---

## Getting Started

### Prerequisites

- Docker Engine 24+
- Docker Compose V2
- GNU Make

### 1. Clone and configure

```bash
git clone https://github.com/mserwach/Order-Inventory-Management-Platform.git
cd Order-Inventory-Management-Platform
cp .env .env.local   # Customize if needed
```

### 2. Full environment setup (one command)

```bash
make setup
```

This runs: `docker compose up → composer install → JWT keys → DB create → migrations → fixtures → search index`.

### 3. Access the services

| Service | URL |
|---------|-----|
| **API** | http://localhost:8080/api |
| **API Docs (OpenAPI)** | http://localhost:8080/api/docs |
| **RabbitMQ Management** | http://localhost:15672 (guest/guest) |
| **OpenSearch Dashboards** | http://localhost:5601 |
| **MailHog** | http://localhost:8025 |

### 4. Authenticate

```bash
# Register an organization
curl -X POST http://localhost:8080/api/v1/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "organization_name": "My Company",
    "plan": "growth",
    "email": "admin@mycompany.com",
    "password": "password123",
    "first_name": "Jane",
    "last_name": "Doe"
  }'

# Login → get JWT
curl -X POST http://localhost:8080/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{"username": "admin@mycompany.com", "password": "password123"}'

# Use demo fixture accounts
# owner@acme-corp.dev / password123
# owner@beta-supplies.dev / password123
```

---

## API Reference

### Authentication

| Method | Path | Description |
|--------|------|-------------|
| POST | `/api/v1/auth/register` | Register organization + owner |
| POST | `/api/v1/auth/login` | Login → JWT + refresh token |
| POST | `/api/v1/auth/refresh` | Refresh JWT |

### Users

| Method | Path | Description |
|--------|------|-------------|
| GET | `/api/v1/users/me` | Current user profile |
| GET | `/api/v1/users/me/organization` | Current organization |

### Orders

| Method | Path | Description |
|--------|------|-------------|
| GET | `/api/v1/orders` | List orders (filterable, paginated) |
| POST | `/api/v1/orders` | Place new order |
| GET | `/api/v1/orders/{id}` | Get order details |
| POST | `/api/v1/orders/{id}/payment` | Confirm payment |
| POST | `/api/v1/orders/{id}/cancel` | Cancel order |

**List Orders — Query Parameters:**

| Param | Type | Description |
|-------|------|-------------|
| `page` | int | Page number (default: 1) |
| `limit` | int | Items per page (max: 100) |
| `status` | string | Filter by status |
| `customer_id` | string | Filter by customer |
| `from` | date | Filter from date |
| `to` | date | Filter to date |
| `sort_by` | string | Sort field |
| `sort_dir` | asc/desc | Sort direction |

### Products

| Method | Path | Description |
|--------|------|-------------|
| GET | `/api/v1/products` | Search products (OpenSearch) |
| POST | `/api/v1/products` | Create product |

### Inventory

| Method | Path | Description |
|--------|------|-------------|
| GET | `/api/v1/inventory/stock/{productId}` | Get stock levels |
| POST | `/api/v1/inventory/stock/{productId}/adjust` | Adjust stock |

### Health

| Method | Path | Description |
|--------|------|-------------|
| GET | `/health` | Health check (DB, cache) |

---

## Event Flow

All domain events are immutable, carry a `eventId` (UUIDv7) and `occurredAt` timestamp, and are dispatched via Symfony Messenger.

```mermaid
graph LR
    PlaceOrder["PlaceOrder\nCommand"] -->|sync| Handler["PlaceOrder\nHandler"]
    Handler -->|emits| OC["OrderCreated\nEvent"]
    OC -->|async RabbitMQ| RI["ReserveInventory\nHandler"]
    RI -->|sync| RSC["ReserveStock\nCommand"]
    RSC -->|emits| SR["StockReserved\nEvent"]
    RI -->|on success| Confirm["Order.confirm()"]
    Confirm -->|emits| OCon["OrderConfirmed\nEvent"]
    RI -->|on fail| Cancel["CancelOrder\nCommand"]
    Cancel -->|emits| OCan["OrderCancelled\nEvent"]

    ConfirmPayment["ConfirmPayment\nCommand"] -->|sync| CPH["ConfirmPayment\nHandler"]
    CPH -->|emits| PC["PaymentConfirmed\nEvent"]
    PC -->|async| GI["GenerateInvoice\nHandler"]
    GI -->|emits| IG["InvoiceGenerated\nEvent"]
```

### Retry Strategy

All async transports use exponential backoff:
- Max retries: 3
- Initial delay: 1000ms
- Multiplier: 2x
- Failed messages go to `failed` transport (Doctrine queue) for manual review

---

## Development Guide

```bash
# Daily workflow
make up            # Start services
make logs          # Follow logs
make shell         # PHP container shell

# Database
make db-migrate    # Run migrations
make db-fixtures   # Load seed data
make db-diff       # Generate migration from entity changes

# Code quality (run before pushing)
make quality       # lint + phpstan + cs-check

# Tests
make test          # All suites
make test-unit     # Unit only
make test-coverage # With HTML report

# Workers
make worker-start  # Start async workers (foreground)
make failed-show   # Inspect failed messages
make failed-retry  # Retry all failed messages
```

### JWT Key Generation

```bash
make jwt-generate
# Or manually:
openssl genrsa -out config/jwt/private.pem 4096
openssl rsa -pubout -in config/jwt/private.pem -out config/jwt/public.pem
```

### Xdebug

Xdebug is installed in the dev image. Configure your IDE to listen on port `9003` with `host.docker.internal` as the client host.

```bash
# VS Code: launch.json
{
  "type": "php",
  "request": "launch",
  "port": 9003,
  "pathMappings": {
    "/var/www/html": "${workspaceFolder}"
  }
}
```

---

## Testing

```
tests/
├── Unit/                  ← Pure domain logic, no I/O
│   ├── Order/Domain/
│   ├── Inventory/Domain/
│   └── Shared/Domain/
├── Integration/           ← Symfony kernel, real DB
│   └── Order/
└── Functional/            ← Full HTTP request/response cycle
```

### Running specific suites

```bash
make test-unit
make test-integration
bin/phpunit --filter OrderTest
```

---

## Deployment

### Production Build

```bash
# Build production images
docker build --target prod -t oimp/php:latest -f docker/php/Dockerfile .
docker build -t oimp/nginx:latest -f docker/nginx/Dockerfile .

# Required environment variables for production
APP_ENV=prod
APP_SECRET=<32-char-random>
DATABASE_URL=postgresql://user:pass@host/db
MESSENGER_TRANSPORT_DSN=amqp://user:pass@host/vhost
REDIS_URL=redis://host:6379
JWT_PASSPHRASE=<strong-passphrase>
```

### Migrations in CI/CD

```bash
bin/console doctrine:migrations:migrate --no-interaction
```

### Health Check

```
GET /health
→ 200 OK  {"status": "ok", "checks": {"database": true}}
→ 503 Service Unavailable if any check fails
```

---

## Scaling Notes

### Horizontal Scaling

- **PHP-FPM**: Stateless — scale freely behind a load balancer
- **Workers**: Run multiple Supervisor instances; RabbitMQ handles distribution
- **Read replicas**: Use Doctrine's `slave` connection for queries
- **Rate limiting**: Redis-backed, works across instances

### Caching Strategy

| Layer | TTL | Invalidation |
|-------|-----|-------------|
| Products | 3600s | On `ProductUpdated` event |
| Organizations | 1800s | On `OrganizationUpdated` |
| Inventory | 60s | On `InventoryAdjusted` |

### Database

- All UUIDs use UUIDv7 (time-ordered) for index locality
- Optimistic locking on `Order` and `StockEntry` prevents lost updates
- Tenant filter runs as a SQL-level filter (no application join)
- PostgreSQL sequences for order numbers (atomic, no gaps under normal operation)

---

## Roadmap

- [ ] Saga pattern for distributed transactions
- [ ] Webhook delivery system
- [ ] OpenTelemetry tracing integration
- [ ] GraphQL endpoint
- [ ] Audit log BC
- [ ] Bulk import/export (CSV, XLSX)
- [ ] Multi-currency support
- [ ] Tax calculation service
- [ ] Carrier API integrations (FedEx, UPS)
- [ ] Customer portal (separate BC)

---

## Architecture Decision Records

| ADR | Decision | Rationale |
|-----|----------|-----------|
| 001 | Modular monolith over microservices | Simpler ops, easy to split later |
| 002 | UUIDv7 as all primary keys | Time-ordered for index locality |
| 003 | Optimistic locking on StockEntry | Prevents overselling without explicit locks |
| 004 | Domain events dispatched async | Decouples BCs, improves resilience |
| 005 | SQL-level tenant filter | Invisible to application code, leak-proof |
| 006 | CQRS-lite (same DB, separate paths) | Clean separation without infrastructure cost |
| 007 | OpenSearch for product search | Full-text, facets, without DB pressure |

---

*Built with ❤️ as a senior backend engineering portfolio project.*
