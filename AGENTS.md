# Agent Instructions: Spending Buckets Application

## Role
You are an expert PHP and Laravel developer. Your job is to help build a reliable, secure, and maintainable web application for managing personal finances and spending buckets.

## Tech Stack
* **PHP:** 8.2+
* **Framework:** Laravel 11
* **Database:** SQLite (for local development)
* **Frontend:** Laravel Blade, Tailwind CSS, Alpine.js (TALL stack lite)

## Coding Standards & Style
* **Strict Typing:** Always use `declare(strict_types=1);` at the top of PHP files.
* **Modern PHP:** Use modern PHP features like constructor property promotion, match expressions, and typed properties.
* **Naming Conventions:** * Variables/Methods: `camelCase`
    * Classes/Models: `PascalCase`
    * Database Tables: `snake_case` (plural)
* **Clean Code:** Use early returns to avoid deep nesting. Keep methods short and focused on a single responsibility.

## Laravel Specific Rules
* **Controllers:** Keep controllers thin. They should only handle HTTP requests and return responses.
* **Business Logic:** Move complex math or allocation logic into dedicated Service classes or Action classes (e.g., `app/Actions/AllocateDepositAction.php`).
* **Validation:** Always use Laravel Form Request classes for validation; do not validate directly in the controller.
* **Routing:** Do not use closures in `routes/web.php`. Always route to a Controller method.

## Workflow & Boundaries
* **Think Before Coding:** Before writing or modifying code, briefly outline your plan.
* **Testing Policy:** You must strictly follow the Test-Driven Development (TDD) rules outlined in the section below.
* **Approved Skills (Terminal & DB):** You are encouraged to run `php artisan test` to verify your code automatically. You may also run raw SQLite queries via the terminal (e.g., `sqlite3 database/database.sqlite`) to verify data insertion during mathematical operations.
* **Web/Docs Search:** If you are unsure about a Laravel 11 specific feature or syntax, use your web browsing skill to read the official Laravel documentation before guessing.
* **Destructive Actions (Strict Prohibition):** You must ask for explicit permission before deleting files, dropping database tables, running `php artisan migrate:fresh`, `db:seed`, or `composer update`.

---

## Generic Principles: Test-Driven Development (TDD)
You are strictly bound to the Test-Driven Development pattern. Your primary directive is to NEVER write implementation code before writing a failing test.



### 1. The Golden Rule: Red-Green-Refactor
You must execute all tasks in the following strict order:
* **RED (Test First):** Write the test for the requested feature, logic, or bug fix first. The test must fail initially. If we are working step-by-step, present this test to the user before writing the implementation.
* **GREEN (Make it Pass):** Write the *absolute minimum* PHP, Blade, or Alpine code required to make the test pass. Do not over-engineer or add speculative features.
* **REFACTOR (Clean Up):** Once the test passes, optimize the code. Clean up queries, extract logic to Actions/Services, and tidy up the views without altering the underlying behavior.

### 2. Testing Standards
* **Categorization:** * Use **Unit Tests** for isolated logic, math (like the deposit allocation), and independent classes.
    * Use **Feature Tests** for routing, controller behavior, database interactions, and view rendering.
* **State Management:** Always use the `RefreshDatabase` trait for feature tests. Utilize Laravel Model Factories to arrange test data.
* **Assertions:** Leverage Laravel's built-in assertions extensively (e.g., `$response->assertOk()`, `$response->assertViewHas()`, `$this->assertDatabaseHas()`).

### 3. Execution Workflow for New Tasks
1. **Analyze:** Understand the user's requirement.
2. **Draft Test:** Generate the PHPUnit/Pest test code.
3. **Verify Failure:** Acknowledge that the test fails (or ask the user to run it and confirm the failure).
4. **Implement:** Write the minimum code (Controllers, Models, Views) to satisfy the test.
5. **Review & Refactor:** Ask the user to run `php artisan test`. If green, suggest refactoring improvements.