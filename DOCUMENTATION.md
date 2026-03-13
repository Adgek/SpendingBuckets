# Spending Buckets — Development Documentation

A chronological record of every commit in this project, explaining what was built and why.

---

## Phase 1: Foundation
**Commit:** `f420ff1` — *Initial commit: Phase 1 Foundation - Models, Migrations, Factories, and Tests*
**Date:** 2026-03-11

Laid the groundwork for the entire application. This commit establishes the three core domain models — **Bucket**, **Deposit**, and **Transaction** — along with their database migrations, Eloquent relationships, and factories.

Key decisions made here:
- **Bucket** uses soft deletes so historical transactions are never orphaned, and its `balance` is a computed accessor (`getBalanceAttribute()`) that sums related transactions rather than storing a static column. This is a core architectural rule.
- **Transaction** acts as the ledger — the single source of truth for all money movement. It links to a Bucket (required) and optionally to a Deposit.
- **13 feature tests** verify schema correctness, model relationships, and the dynamic balance calculation.

---

## Housekeeping
**Commit:** `bb16cb1` — *updated Agents to include some skills*
**Date:** 2026-03-11

Minor update to `AGENTS.md` to include additional AI agent skill instructions. No application code changed.

---

## Phase 2: The Ledger
**Commit:** `832ae4b` — *Phase 2: The Ledger — Transaction type constants, 13 ledger tests, architecture updates*
**Date:** 2026-03-11

Formalized the four transaction types by adding constants to the Transaction model:
- `TYPE_ALLOCATION` — money flowing into a bucket from a deposit
- `TYPE_EXPENSE` — money leaving a bucket (e.g., paying a bill)
- `TYPE_SWEEP` — end-of-month cleanup moving leftover funds to savings
- `TYPE_TRANSFER` — moving money between two buckets

**13 new ledger tests** prove that:
- Allocations increase a bucket's balance; expenses decrease it.
- Transfers are atomic paired operations linked by a shared `reference_id` and wrapped in `DB::transaction()`.
- Sweeps move the full balance of a bucket to savings atomically.
- A bucket can go negative (by design — no guard rails at the ledger level).
- A complex mixed-operations scenario maintains zero-sum integrity across the system.

**33 total tests passing.**

---

## Phase 3: The Allocation Engine
**Commit:** `bcd0e9a` — *Phase 3: Implement ProcessDepositAction with 14 TDD tests*
**Date:** 2026-03-11

Built the heart of the application: `ProcessDepositAction` in `app/Actions/`. This is the **Sequential Priority Waterfall** engine that decides where deposited money goes.

When a deposit arrives, the action:
1. Fills **fixed** buckets in `priority_order`, deducting from the deposit as it goes. If funds run out mid-queue, it halts (shortfall scenario).
2. Respects already-funded amounts within the same month (e.g., a second paycheck doesn't double-fill buckets).
3. Distributes any **excess** funds to `excess`-type buckets based on their `excess_percentage`.
4. Enforces bucket **caps** — if a bucket would exceed its cap, the overflow is rerouted.
5. Dumps any mathematically un-allocatable remainder (rounding cents) into a savings catch-all.

**14 TDD tests** cover sequential fill, shortfall halt, multi-deposit months, excess distribution, cap enforcement, rounding, and edge cases. **47 total tests passing.**

---

## Phase 3 Bug Fixes
**Commit:** `7d0444d` — *Fix ProcessDepositAction: 6 concerns addressed*
**Date:** 2026-03-11

A code-review pass that hardened the engine:
1. **No silent money loss** — throws `RuntimeException` if no primary savings bucket exists to catch overflow.
2. **`is_primary_savings` flag** — added a boolean column to `buckets` replacing a fragile "first uncapped bucket" heuristic for identifying the savings catch-all.
3. **Fixed buckets respect caps** — allocation now checks room-under-cap, not just monthly target.
4. **Division-by-zero guard** — handles the edge case where all excess percentages sum to 0.
5. **Test location fix** — moved test using `RefreshDatabase` from `Unit/` to `Feature/`.
6. **Boundary test** — added a test for a deposit that exactly matches the sum of all fixed targets.

**54 tests, 107 assertions passing.**

---

## Phase 4: API / Controllers (Initial)
**Commit:** `8f53032` — *Phase 4: API/Controllers — deposits, expenses, transfers, and bucket CRUD*
**Date:** 2026-03-11

First pass at the HTTP layer. Built a full JSON API:
- **BucketController** — CRUD with balance included in responses.
- **DepositController** — creating a deposit triggers `ProcessDepositAction` automatically.
- **ExpenseController** — records a negative expense transaction against a bucket.
- **TransferController** — creates paired transfer transactions with a shared UUID.
- **5 Form Request classes** for validation (keeping controllers thin per project conventions).

**23 new feature tests. 77 total passing.**

---

## Phase 4: Web Refactor
**Commit:** `f7c111e` — *Phase 4: Refactor API routes to web routes with Blade views*
**Date:** 2026-03-11

Pivoted from a JSON API to a server-rendered Blade UI:
- Replaced all API endpoints with web routes returning Blade views.
- Controllers now return `view()` on GET and `redirect()->with('success', ...)` on POST/PUT/DELETE.
- Created Tailwind-styled Blade templates: app layout, bucket index/create/edit/show, deposit form, expense form, and transfer form.
- Removed `routes/api.php` entirely; all routing lives in `routes/web.php`.
- Tests updated to use session-based assertions (`assertSessionHasErrors`, `assertRedirect`, `assertViewIs`).

**81 tests passing (196 assertions).**

---

## Phase 4: Code Review Fixes
**Commit:** `3d81fe8` — *Fix 7 code review issues in Phase 4 controllers/views*
**Date:** 2026-03-11

Polish pass addressing 7 review findings:
1. **Named routes** — replaced all hardcoded URLs with `route()` helpers across controllers, views, and tests.
2. **Query optimization** — `BucketController@index` now uses `withSum('transactions', 'amount')` instead of eager-loading entire transaction collections.
3. **Model consistency** — verified `is_primary_savings` is in the migration, `$fillable`, and `$casts`.
4. **Dollar input UX** — forms accept human-friendly dollar amounts (e.g., `45.00`); Form Requests convert to cents via `prepareForValidation()`.
5. **Error handling** — `DepositController` catches `RuntimeException` from the engine, flashes an error, and rolls back.
6. **DB-level sorting** — `show()` uses an ordered eager-load query instead of in-memory `sortByDesc`.
7. **Delete guard** — blocks deletion of buckets with a positive balance; the edit view disables the delete button and shows a warning.

**84 tests passing (206 assertions).**

---

## Current Status

| Phase | Status | Tests |
|-------|--------|-------|
| Phase 1: Foundation (Models, Migrations, Factories) | ✅ Complete | 13 |
| Phase 2: The Ledger (Transaction types & integrity) | ✅ Complete | 13 |
| Phase 3: The Engine (ProcessDepositAction) | ✅ Complete | 21 |
| Phase 4: API/Controllers & Blade UI | ✅ Complete | 37 |
| Phase 5: Front-end integration (drag-and-drop, polish) | 🔲 Not started | — |
| **Total** | | **84 tests, 206 assertions** |
