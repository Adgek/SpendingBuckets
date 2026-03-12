# Design Document: Advanced Spending Buckets Application

**Architecture Type:** Local, Single-User, Sequential Priority Ledger System
**Target Framework:** Laravel (PHP)

## 1. System Overview
This application is a highly automated "Envelope Budgeting" system. It moves away from naive percentage-based splits and utilizes a **Sequential Priority Waterfall** allocation method. It handles fixed monthly costs, dynamically routes excess funds, manages discrete outflows, and maintains historical accuracy via a double-entry ledger system. There is no multi-user authentication required.

**Core Directives for AI Agent:**
* **DO NOT** use a static `balance` column on the `Bucket` model. Balances must be calculated dynamically by summing `Transactions`.
* **DO NOT** implement user authentication or `user_id` foreign keys. This is a local, single-tenant application.
* **MUST** follow Test-Driven Development (TDD). Write tests for the Action classes and Ledger logic before generating the final implementation.

## 2. Database Schema

### Table: `buckets`
Represents the envelopes where money is stored. Includes configuration for how the bucket behaves during the allocation and sweep phases.
* `id` (Primary Key)
* `name` (String, e.g., "Mortgage", "Water Bill", "Emergency Fund")
* `type` (Enum/String: `fixed` or `excess`)
* `monthly_target` (Integer, in cents. Nullable. Required for `fixed` buckets)
* `priority_order` (Integer. Used to rank `fixed` buckets for sequential filling)
* `cap` (Integer, in cents. Nullable. The absolute maximum balance the bucket can hold)
* `sweeps_excess` (Boolean. Default `false`. If `true`, end-of-month remaining balance is transferred to savings)
* `excess_percentage` (Integer. Nullable. Used only for `excess` buckets to split leftover deposit funds)
* `timestamps`
* `softDeletes` (Required so historical transactions tied to a deleted bucket don't break)

### Table: `deposits`
Records the overarching inflow event.
* `id` (Primary Key)
* `amount` (Integer, in cents)
* `deposit_date` (Date. Crucial for calculating which month's target we are currently funding)
* `description` (String, nullable)
* `timestamps`

### Table: `transactions` (The Ledger)
The single source of truth for all money movement.
* `id` (Primary Key)
* `bucket_id` (Foreign Key -> `buckets.id`)
* `deposit_id` (Foreign Key -> `deposits.id`. Nullable. Only used if the transaction is an inflow from a deposit)
* `amount` (Integer, in cents. Positive for inflows, negative for outflows)
* `type` (Enum/String: `allocation`, `expense`, `sweep`, `transfer`)
* `reference_id` (UUID. Nullable. Used to link two transactions together, e.g., transferring money between buckets)
* `description` (String, nullable)
* `created_at` (Timestamp)

## 3. The Allocation Engine (Inflow Logic)

When a user submits a new Deposit, the system must execute the `ProcessDepositAction` following these exact sequential rules:

1. **Contextualize the Month:** Determine the current month based on the `deposit_date`.
2. **Fetch Priority Queue:** Retrieve all `fixed` buckets, ordered ascending by `priority_order`.
3. **The Sequential Fill Loop:**
   * For each bucket, calculate the **Already Funded Amount** (sum of `allocation` transactions for this specific month).
   * Calculate **Remaining Need** (`monthly_target` - Already Funded Amount).
   * If `Remaining Need > 0` AND there are still funds in the Deposit:
     * Create a positive `allocation` Transaction for the lesser of (Remaining Need) OR (Remaining Deposit Funds).
     * Deduct this amount from the running Deposit total.
   * If the Deposit total reaches $0, **HALT THE LOOP**. (This handles shortfalls).
4. **The Excess Distribution:**
   * If the Fill Loop completes and the Deposit total is > 0 (e.g., a 5th paycheck month or unexpected windfall), fetch all `excess` buckets.
   * Distribute the remaining funds based on their `excess_percentage`.
   * **Cap Check:** Before creating the transaction, check if the allocation will exceed the bucket's `cap`. If it does, only allocate up to the cap, and keep the remainder in a temporary pool.
   * Any mathematically un-allocatable leftover cents (or funds blocked by caps) should be dumped into a designated primary "Savings" bucket.

## 4. The Outflow & Reconciliation Engine

### Standard Expenses
* Triggered manually by the user (e.g., "Paid Water Bill: $45").
* Creates a single `expense` Transaction with a negative amount on the specified bucket.

### Manual Transfers ("Danger Mode")
* Moving money between buckets to cover unexpected life events.
* Requires generating a shared UUID (`reference_id`).
* Creates two `transfer` Transactions simultaneously:
  1. Negative amount on the source bucket.
  2. Positive amount on the destination bucket.

### End-of-Month Sweep (Cron/Action)
* Runs at the end of a given month.
* Fetches all buckets where `sweeps_excess = true` (e.g., Groceries, Water Bill).
* Calculates the current total balance of the bucket.
* If `balance > 0`, it creates a negative `sweep` Transaction on the source bucket bringing its balance to 0, and a corresponding positive `sweep` Transaction into the primary "Excess/Savings" bucket.
* Buckets where `sweeps_excess = false` (e.g., Emergency Fund) are ignored and retain their balances into the next month.

## 5. Execution Phases for AI Agent

* **Phase 1: Foundation.** Generate Migrations, Models, and Factories. Ensure `Bucket` has a `getBalanceAttribute()` method that sums its `transactions`. 
* **Phase 2: The Ledger.** Implement the `Transaction` model logic. Write tests proving money can move in, out, and between buckets accurately.
* **Phase 3: The Engine.** Implement the `ProcessDepositAction`. Write extensive unit tests covering the "Shortfall" scenario and the "5-Paycheck/Excess" scenario.
* **Phase 4: API/Controllers.** Create the endpoints to trigger deposits, expenses, and transfers.
* **Phase 5: Front-end integration.** Expose data for the UI, including a drag-and-drop endpoint to update `priority_order` on buckets.