# Copilot Instructions for AI Coding Agents

## Developer profile: Symfony Backend Developer

-   Proficient in PHP and Symfony framework
-   Understanding of RESTful API principles and API Platform
-   Experience with JWT authentication and security best practices
-   Knowledge of Docker and local development environments
-   Familiarity with payment processing (Stripe) and QR code generation

## Project Overview

This is a Symfony-based backend for ticketing and event management, integrating Stripe payments, QR code validation, and user management. The architecture is modular, with clear separation between API resources, controllers, services, state processors, and entities.

## Key Components & Structure

-   `src/Entity/` — Doctrine entities for core domain models (Ticket, Event, User, etc.)
-   `src/Controller/` — API controllers (e.g., `StripeWebhookController`, `TicketController`) handle HTTP requests and business logic
-   `src/Service/` — Reusable services (e.g., `QrCodeService`, `TicketEmailService`)
-   `src/State/` — Custom state processors for API Platform, often decorating default processors (see `config/services.yaml`)
-   `src/Repository/` — Custom Doctrine repositories for advanced queries
-   `config/packages/` — Symfony and third-party bundle configuration (API Platform, JWT, CORS, etc.)
-   `config/routes/` — Route definitions for API endpoints
-   `migrations/` — Database migration scripts
-   `tests/Entity/` — PHPUnit tests for entities

## Developer Workflows

-   **Environment Setup:**
    -   Copy `.env` to `.env.local` and adjust credentials
    -   Example: `cp .env .env.local && nano .env.local`
    -   Set `DATABASE_URL` for MySQL
-   **Build & Run:**
    -   Use Symfony CLI or Docker Compose for local development
    -   Common commands:
        -   `php bin/console doctrine:migrations:migrate` — Run DB migrations
        -   `symfony server:start` or `docker-compose up` — Start local server
-   **Testing:**
    -   Run PHPUnit tests: `./vendor/bin/phpunit`
    -   Test coverage is focused on entity logic and business rules
-   **Debugging & Monitoring:**
    -   Tail logs: `tail -f var/log/dev.log`
    -   Use Symfony profiler and web debug toolbar

## Patterns & Conventions

-   **Enums (PHP 8.1+)** are used for statuses (see `src/Enum/`)
-   **Helper methods** in entities (e.g., `Ticket::isUsed()`, `RefundRequest::markAsProcessed()`)
-   **State Processors** decorate API Platform's default processors for custom persistence logic (see `config/services.yaml`)
-   **Stripe Integration:**
    -   Ticket creation triggers Stripe Checkout
    -   Stripe webhooks update ticket status and generate QR codes
    -   QR code validation handled via dedicated controller/service
-   **CORS** configured in `config/packages/nelmio_cors.yaml`

## Integration Points

-   **Stripe:** Payment and webhook flows (`StripeWebhookController`)
-   **JWT Auth:** Configured via `config/packages/lexik_jwt_authentication.yaml`
-   **API Platform:** Exposes REST endpoints for entities
-   **Mailer:** Email notifications via `TicketEmailService`

## Example Data Flow

1. Ticket creation → Stripe Checkout Session
2. Stripe webhook → updates ticket (status, QR code)
3. QR code validation at event entry

## References

-   See `README.md` for setup, troubleshooting, and API examples
-   Key configs: `config/services.yaml`, `config/packages/`, `config/routes/`
-   Main entry: `public/index.php`, kernel: `src/Kernel.php`

---

**Feedback:** If any section is unclear or missing, please specify so it can be improved for future AI agents.
