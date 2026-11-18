# Tiny Library API

![Coverage](https://img.shields.io/badge/coverage-83.9%25-brightgreen?style=for-the-badge)

A RESTful API for managing a library's catalog, members, borrowing activity, and penalties. The application provides separate authenticated workflows for **librarians** and **members**, with token-based authentication and a versioned JSON API.

## Contents

- [Features](#features)
- [Technology stack](#technology-stack)
- [Requirements](#requirements)
- [Getting started](#getting-started)
- [Authentication](#authentication)
- [API reference](#api-reference)
- [Validation and response conventions](#validation-and-response-conventions)
- [Running tests](#running-tests)
- [Project structure](#project-structure)
- [Development notes](#development-notes)

## Features

### Librarian operations

- Authenticate and revoke the current access token.
- Add, search, update, and remove books.
- Register, search, update, activate, deactivate, and remove members.
- Create, search, update, and remove categories.
- Record penalty payments.

### Member operations

- Authenticate and revoke the current access token.
- Search the book catalog.
- Borrow available books, subject to borrowing rules.
- Search personal borrowing records.
- Return borrowed books.
- View personal penalties.

### Application behavior

- Separate authentication guards for members and librarians.
- Laravel Sanctum personal access tokens.
- Request validation through dedicated Form Request classes.
- Domain logic organized into services and repositories.
- Redis-backed book-search caching.
- Database-backed relationships between books, categories, members, borrows, and penalties.

## Technology stack

| Component | Technology |
|---|---|
| Language | PHP 8.3+ |
| Framework | Laravel 13 |
| Authentication | Laravel Sanctum |
| Database | MySQL by default in `.env.example`; other Laravel-supported drivers may be configured |
| Redis client | Predis |
| Automated tests | PHPUnit 12 / Laravel test runner |

The exact dependency constraints are maintained in [composer.json](composer.json).

## Requirements

Before running the application, install:

- PHP **8.3 or newer**, with the extensions required by Laravel and your selected database driver.
- Composer.
- A database server (the example environment is configured for MySQL).
- Redis, reachable using the connection settings in your environment.
- Git.

Redis is used directly by the book-cache implementation. Configuring Laravel's general cache store to use the database does not replace that Redis dependency.

## Getting started

### 1. Clone the repository

```bash
git clone https://github.com/Shadmehr1984/tinyLibrary_api.git
cd tinyLibrary_api
```

### 2. Install PHP dependencies

```bash
composer install
```

### 3. Configure the environment

Create your local environment file:

```bash
cp .env.example .env
php artisan key:generate
```

Update the database and Redis settings in `.env`. The committed example uses MySQL:

```dotenv
APP_NAME="Tiny Library API"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=book_management
DB_USERNAME=root
DB_PASSWORD=

REDIS_CLIENT=predis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
REDIS_PASSWORD=null
```

Create the configured database before running migrations. Use credentials appropriate for your own environment; do not commit your real `.env` file.

### 4. Run database migrations

```bash
php artisan migrate
```

### 5. Start the application

```bash
php artisan serve
```

The local API base URL will normally be:

```text
http://127.0.0.1:8000/api
```

You can check that the Laravel server responds by opening the base URL in your API client. The API endpoints themselves are listed below.

## Authentication

The API has two distinct authenticated roles:

- **Librarian:** protected by the `auth:librarian` guard.
- **Member:** protected by the `auth:member` guard. Protected member routes also require the member to be active.

Login endpoints are public. A successful login returns an access token. Send that token on subsequent protected requests using the HTTP Bearer authentication scheme:

```http
Authorization: Bearer YOUR_ACCESS_TOKEN
Accept: application/json
Content-Type: application/json
```

Use the token returned by the corresponding login endpoint. A member token is not a substitute for a librarian token, or vice versa. Logging out revokes the current access token.

## API reference

All endpoints are prefixed with `/api` by Laravel. The tables below show the complete request paths, including that prefix.

### Librarian endpoints

| Method | Endpoint | Purpose | Authentication |
|---|---|---|---|
| POST | `/api/v1/librarian/login` | Log in a librarian | Public |
| POST | `/api/v1/librarian/logout` | Log out and revoke current token | Librarian |
| POST | `/api/v1/librarian/add_book` | Add a book | Librarian |
| DELETE | `/api/v1/librarian/delete_book` | Remove a book by ISBN | Librarian |
| POST | `/api/v1/librarian/search_book` | Search books | Librarian |
| PUT | `/api/v1/librarian/update_book` | Update a book | Librarian |
| POST | `/api/v1/librarian/add_member` | Register a member | Librarian |
| PUT | `/api/v1/librarian/deactivate_member` | Deactivate a member | Librarian |
| PUT | `/api/v1/librarian/activate_member` | Activate a member | Librarian |
| DELETE | `/api/v1/librarian/delete_member` | Remove a member | Librarian |
| POST | `/api/v1/librarian/search_member` | Search members | Librarian |
| PUT | `/api/v1/librarian/update_member` | Update a member | Librarian |
| POST | `/api/v1/librarian/add_category` | Create a category | Librarian |
| DELETE | `/api/v1/librarian/delete_category` | Remove a category | Librarian |
| POST | `/api/v1/librarian/search_category` | Search categories | Librarian |
| PUT | `/api/v1/librarian/update_category` | Update a category | Librarian |
| PUT | `/api/v1/librarian/pay_penalty` | Pay a penalty | Librarian |

### Member endpoints

| Method | Endpoint | Purpose | Authentication |
|---|---|---|---|
| POST | `/api/v1/member/login` | Log in a member | Public |
| POST | `/api/v1/member/logout` | Log out and revoke current token | Active member |
| POST | `/api/v1/member/search_book` | Search the book catalog | Active member |
| POST | `/api/v1/member/add_borrow` | Borrow a book | Active member |
| POST | `/api/v1/member/search_borrow` | Search the authenticated member's borrows | Active member |
| PUT | `/api/v1/member/back_borrow` | Return a borrowed book | Active member |
| POST | `/api/v1/member/search_penalty` | Search the authenticated member's penalties | Active member |

> **Note:** The route file also contains a `GET /api/test` route protected by librarian authentication. It is a development/test route, not part of the versioned v1 endpoint list.

### Request examples

Login (the exact required fields are validated by the corresponding login request):

```http
POST /api/v1/member/login
Accept: application/json
Content-Type: application/json
```

A protected member request:

```http
POST /api/v1/member/search_book
Authorization: Bearer YOUR_MEMBER_TOKEN
Accept: application/json
Content-Type: application/json
```

A protected librarian request:

```http
POST /api/v1/librarian/search_book
Authorization: Bearer YOUR_LIBRARIAN_TOKEN
Accept: application/json
Content-Type: application/json
```

Search endpoints accept their supported filters as JSON request fields. Search criteria and field validation differ by endpoint; consult the relevant Form Request in `app/Http/Requests` for the authoritative field-level contract.

## Validation and response conventions

- Send and request JSON using `Accept: application/json` and `Content-Type: application/json`.
- Laravel validation failures normally return **422 Unprocessable Entity** with field-level error details.
- Authentication failures return **401 Unauthorized**.
- Authenticated users without permission, or inactive members blocked by middleware, may receive **403 Forbidden**.
- Some domain conflicts have endpoint-specific responses; for example, attempting to pay an already-paid penalty returns **409 Conflict**.
- Successful create operations generally return **201 Created**; other successful operations generally return **200 OK**.

The endpoint contract is defined by the current routes, Form Requests, controllers, services, and tests. For detailed internal endpoint analysis, see [the endpoint contract matrix](.agent/endpoint-contract-matrix.md).

## Test coverage

The latest coverage figure reported by the project maintainer is **83.9% line coverage** (2026-11-18), with the PHPUnit source scope configured to include `app/`.

This is the last reported measurement, not a live metric. It predates the recently added focused `MemberBuilder` unit tests; rerun coverage to obtain the updated percentage.

## Running tests

Install dependencies first, then run the Laravel test suite:

```bash
php artisan test
```

Or use the Composer script:

```bash
composer test
```

The independent API Feature tests are organized under `tests/Feature/Api` and use shared test support from `tests/Support`. They are designed to isolate Redis calls with a strict mock so tests do not need to connect to a developer's Redis server.

For coverage reporting, configure a PHPUnit coverage driver such as Xdebug or PCOV and run:

```bash
vendor/bin/phpunit --coverage-text
vendor/bin/phpunit --coverage-html coverage
```

Open `coverage/index.html` to inspect the HTML report. The source paths included in the report are controlled by the `<source>` section in `phpunit.xml`; make sure it includes the application code you intend to measure.

## Project structure

A simplified overview of the application:

```text
app/
├── Domain/          # Domain value objects and domain-level concepts
├── Http/
│   ├── Controllers/ # API request entry points
│   ├── Middleware/  # Authentication-related request middleware
│   └── Requests/    # Input validation contracts
├── Models/          # Eloquent models
├── Repositories/    # Data access layer
├── Rules/           # Custom validation rules
└── Services/        # Application and business logic

database/
├── factories/
├── migrations/
└── seeders/

routes/
└── api.php          # Versioned API route declarations

tests/
├── Feature/Api/     # Independent API Feature tests
├── Unit/
└── Support/         # API test base case, factories, and helpers

.agent/
├── endpoint-contract-matrix.md
└── endpoint-testing-plan.md
```

## Development notes

- Work against the API paths with the `/api` prefix. Laravel adds this prefix automatically to routes declared in `routes/api.php`.
- Keep API behavior and response contracts consistent with the existing endpoint definitions.
- Read [AGENTS.md](AGENTS.md) and the relevant files under `.agent/` before continuing work that affects the API test suite.
- The endpoint testing plan is the source of truth for test scope, phase status, and remaining work. Update it as meaningful testing work is completed.
- Do not commit local secrets, access tokens, or environment files.

## License

No project-specific license has been documented in this README. Check with the repository owner before redistributing this project or treating it as open source.
