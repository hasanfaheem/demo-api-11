# PRO API — Patient Reported Outcomes

A RESTful API built with Laravel 11, MySQL 8, and PHP 8.4 for managing patient-reported outcome submissions across clinical instruments.

---

## Setup Instructions

### Requirements
- PHP 8.4+
- Laravel 11
- MySQL 8+
- Composer

### Installation

```bash
git clone <your-fork-url>
cd tti-apis
composer install
cp .env.example .env
php artisan key:generate
```

Configure your `.env`:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=tti_apis
DB_USERNAME=root
DB_PASSWORD=your_password
```

Run migrations and seed:
```bash
php artisan migrate
php artisan db:seed
```

Start the server:
```bash
php artisan serve
```

---

## API Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/patients` | Create a new patient |
| POST | `/api/instruments` | Create an instrument with questions |
| POST | `/api/patients/{id}/submissions` | Submit a completed instrument |
| GET | `/api/patients/{id}/submissions` | List submissions (paginated, newest first) |
| GET | `/api/patients/{id}/submissions/{id}` | Get a single submission with answers |
| GET | `/api/patients/{id}/summary?instrument_id={id}` | Aggregated summary per question type |

---

## API Documentation

The API is documented using OpenAPI 3.0 spec in `openapi.yaml`.

To view it in Swagger UI:

1. Go to [https://editor.swagger.io](https://editor.swagger.io)
2. Paste the contents of `openapi.yaml`
3. The full interactive API documentation will render on the right

---

## Design Decisions

### Single `value` column for answers
All answer types (scale_1_5, yes_no, free_text) are stored as strings in a single `value` column. Type coercion happens at the API Resource layer — integers for scale, booleans for yes/no, strings for free text. This keeps the schema simple and avoids polymorphic complexity that isn't warranted at this scale.

### Conditional aggregation in a single query
The summary endpoint uses a single SQL query with `AVG(CASE WHEN ...)`, `SUM(CASE WHEN ...)`, and `COUNT(CASE WHEN ...)` rather than loading all records into PHP memory. This keeps the summary endpoint performant as submission volume grows.

### Eager loading throughout
All endpoints that return nested relationships use eager loading (`with()`) to prevent N+1 queries.

### Submission validation
The `StoreSubmissionRequest` uses a custom `after()` hook to cross-validate answers against their question's response type after basic validation passes. This catches type mismatches (e.g. a string submitted for a scale question) and ensures all questions in an instrument are answered before persisting anything.

### Transaction on submission store
The submission and its answers are written inside a `DB::transaction()` to ensure atomicity — no partial submissions can exist in the database.

---

## What I Would Add With More Time

- **Authentication** — Sanctum token auth to scope patients to authenticated clinicians
- **Rate limiting** — Per-token rate limiting on submission endpoints to prevent abuse
- **Audit logging** — PHI access log (who accessed which patient record and when)
- **Soft deletes** — On patients and submissions rather than hard deletes
- **OpenAPI/Swagger docs** — Auto-generated from request/resource classes
- **Docker Compose** — For zero-config local setup
- **Caching** — Cache summary results with cache invalidation on new submission
- **Pagination on summary** — If question count grows large

---

## Running Tests

```bash
php artisan test
```

12 tests, 34 assertions — all passing.

---

## Docker Setup

### Requirements
- Docker
- Docker Compose

### Steps

```bash
cp .env.docker .env
docker-compose up -d
docker-compose exec app php artisan key:generate
docker-compose exec app php artisan migrate
docker-compose exec app php artisan db:seed
```

The API will be available at `http://localhost:8000/api`

To run tests inside Docker:
```bash
docker-compose exec app php artisan test
```

To stop:
```bash
docker-compose down
```