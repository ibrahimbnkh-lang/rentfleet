# RentFleet — Vehicle Rental Management Platform

A modular fleet management platform for Algeria, built with **Laravel 12 + Vue 3**.

## Stack

| Layer | Technology |
|---|---|
| Backend | Laravel 12, PHP 8.2+, MySQL 8.4, Redis 7, MinIO |
| Auth | Laravel Sanctum, Argon2id, optional 2FA |
| Frontend | Vue 3 + Vite + TypeScript + Pinia + Vue Router + Tailwind CSS v4 |
| Tests | Pest 3 (feature + architecture tests) |
| I18n | vue-i18n (fr, en; ar ready) |
| Docker | Nginx + PHP-FPM + MySQL + Redis + MinIO |

---

## Quick Start (XAMPP local dev)

### Prerequisites
- XAMPP (PHP 8.2+, MySQL 8.4)
- Node.js 20+
- Composer 2.x

### 1. Clone and install backend

```bash
cd backend
composer install
cp .env.example .env
php artisan key:generate
```

### 2. Create the MySQL database

Open XAMPP phpMyAdmin and create a database named `rentfleet`.

### 3. Run migrations and seeders

```bash
php artisan migrate --seed
```

### 4. Create a test license (Phase 2)

```bash
php artisan license:generate-test-keypair
php artisan license:issue --modules=M00Core,M01Fleet,M02Customers --days=365
```

### 5. Start the backend dev server

```bash
php artisan serve
# API available at http://localhost:8000/api/v1
```

### 6. Install and start the frontend

```bash
cd ../frontend
npm install
npm run dev
# Admin UI at http://localhost:5173
```

### 7. (Optional) Start Docker stack

```bash
# From project root:
docker compose up -d
```

---

## Module Architecture

```
backend/
└── Modules/
    ├── M00Core/          # Branches, users, roles, audit, license, settings
    ├── M01Fleet/         # Vehicles, categories, documents, photos
    └── M02Customers/     # Customers, KYC, blacklist
```

Each module has:
- `module.json` — id, version, prerequisites, permissions
- `Routes/api.php` — module routes (auto-loaded)
- `Database/Migrations/` — isolated migrations
- `Http/Controllers/` — controllers extending `ApiController`
- `Services/` — business logic (no cross-module table access)

**Iron Rule:** Modules communicate only through:
1. Public service interfaces (`App\Contracts\`)
2. Domain events (`App\Events\`)
3. The `ModuleLoader` singleton

---

## API Conventions

- **Base URL**: `/api/v1`
- **Auth**: `Authorization: Bearer <token>`
- **Success envelope**: `{ "data": ..., "meta": ..., "links": ... }`
- **Error envelope**: `{ "error": { "code": "...", "message": "...", "details": {} } }`
- **Pagination**: `?page=1&per_page=15`
- **Dates**: ISO 8601, `Africa/Algiers` timezone
- **Currency**: DZD

---

## Build Status (by Phase)

| Phase | Status | Description |
|---|---|---|
| **Phase 1** | ✅ Completed | Foundation: Docker, Laravel 12, ModuleLoader, Pest 3, Vue shell, Login |
| **Phase 2** | ✅ Completed | M00Core: Branches, Users, Roles & Permissions, Audit Log, License Client, Settings, 2FA, Public Agency Showcase (`/agences/:code`) |
| **Phase 3** | ✅ Completed | M01Fleet: Vehicles, Categories, Documents (Assurance, Contrôle technique, Carte grise, Vignette), Compliance Service, Expiring Alerts |
| **Phase 4** | ✅ Completed | M02Customers: Customers (Individual & Corporate), Algerian NIN & Driver License, KYC Verification Workflow, Blacklist Management & Automated Rental Eligibility |

---

## Permissions Format

```
module.resource.action
```

Examples: `core.branch.view`, `fleet.vehicle.override_compliance`, `customers.kyc.verify`

---

## Default Roles

- **Super Admin** — all permissions
- **Manager** — branch-level management
- **Agent** — vehicle and customer operations
- **Accountant** — read-only + financial views
- **Mechanic** — vehicle status updates only

---

## Running Tests

```bash
cd backend

# Run all tests
php artisan test

# Run only Pest tests with verbose output
./vendor/bin/pest --verbose

# Run only architecture tests
./vendor/bin/pest tests/Architecture/

# Run only unit tests
./vendor/bin/pest tests/Unit/
```

---

## OpenAPI Documentation

The full API spec is in [`docs/openapi.yaml`](docs/openapi.yaml).

To view it interactively, open [Swagger Editor](https://editor.swagger.io/) and paste the file.

---

## Environment Variables

See [`.env.example`](backend/.env.example) for all required variables.

Key variables:
- `DB_*` — MySQL connection (XAMPP: host=127.0.0.1, user=root, password=)
- `REDIS_*` — Redis connection (optional in local dev)
- `AWS_*` — MinIO/S3 file storage
- `RENTFLEET_LICENSE_PUBLIC_KEY` — Ed25519 public key for license validation
