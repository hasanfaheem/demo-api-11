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
APP_NAME=ProAPI
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost:8000

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
| POST | `/api/register` | Register a new user and receive API token |
| POST | `/api/login` | Login and receive API token |
| POST | `/api/logout` | Revoke current API token |
| POST | `/api/patients` | Create a new patient |
| POST | `/api/instruments` | Create an instrument with questions |
| POST | `/api/patients/{id}/submissions` | Submit a completed instrument |
| GET | `/api/patients/{id}/submissions` | List submissions (paginated, newest first) |
| GET | `/api/patients/{id}/submissions/{id}` | Get a single submission with answers |
| GET | `/api/patients/{id}/summary?instrument_id={id}` | Aggregated summary per question type |

All endpoints except `/register` and `/login` require a Bearer token in the Authorization header:
```
Authorization: Bearer <your-token>
```

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

### Summary caching
Summary results are cached for 10 minutes per patient + instrument combination. The cache is invalidated immediately when a new submission is stored. Note: in a stricter PHI compliance environment, caching patient data would require additional review and may be disabled entirely.

### Composite indexes
Composite indexes on `(patient_id, instrument_id)` for submissions and `(submission_id, question_id)` for answers directly serve the most expensive queries — the summary aggregation and the answer join — without relying on separate single-column indexes.

### Rate limiting
Auth endpoints are throttled to 10 requests per minute. All other API endpoints are throttled to 60 requests per minute per token.

---

## What I Would Add With More Time

- **PHI audit logging** — Log every read and write of patient data (who, when, which record) as a compliance requirement for healthcare applications
- **Soft deletes** — On patients and submissions rather than hard deletes, preserving data integrity and supporting audit trails
- **Read replica routing** — Route summary and list queries to a read replica, keeping writes on the primary database
- **Queued submission processing** — Accept submissions instantly via the API and process answer validation and storage asynchronously via a queued job for high-volume scenarios
- **Pagination on summary questions** — If an instrument grows to 50+ questions, the summary response payload becomes large
- **Refresh token support** — Current Sanctum implementation issues a single long-lived token; a refresh token pattern would be more appropriate for clinical applications
- **Role-based access control** — Separate permissions for clinicians (read all patients) vs patients (read own data only)

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
