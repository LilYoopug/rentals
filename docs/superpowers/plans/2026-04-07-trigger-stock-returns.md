# Trigger-Based Rental Stock Implementation Plan

> **For agentic workers:** REQUIRED: Use superpowers:subagent-driven-development (if subagents available) or superpowers:executing-plans to implement this plan. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Move borrowing, returning, and stock synchronization into MySQL triggers while removing the damaged return flow.

**Architecture:** MySQL triggers become the source of truth for stock reservation and release. PHP keeps validation, request shaping, and UI behavior, but no longer mutates stock counters directly or exposes a `flagged` return status.

**Tech Stack:** PHP, MariaDB/MySQL, shell integration tests

---

### Task 1: Lock Trigger Behavior With Tests

**Files:**
- Create: `tests/sql-trigger-stock-flow-test.sh`
- Modify: `tests/rental-return-integrity-test.sh`

- [ ] **Step 1: Write the failing test**
- [ ] **Step 2: Run it to verify it fails on the old schema**
- [ ] **Step 3: Keep existing rental/return integration coverage aligned with the removed damaged flow**
- [ ] **Step 4: Run the tests again after implementation**

### Task 2: Move Stock Rules Into SQL

**Files:**
- Modify: `database/lenscraft.sql`
- Modify: `database/seed-lenscraft.sql`

- [ ] **Step 1: Remove `flagged` from `returns.status`**
- [ ] **Step 2: Add rental insert/update/delete triggers that reserve and release stock**
- [ ] **Step 3: Add return insert/update triggers that complete rentals and rely on rental triggers to release stock**
- [ ] **Step 4: Keep seeded stock values consistent with the trigger-managed rows**

### Task 3: Remove PHP Stock Mutation Code

**Files:**
- Modify: `data/rentals-data.php`
- Modify: `data/returns-data.php`
- Modify: `data/products-data.php`
- Modify: `process/staff-pengembalian-konfirmasi.php`
- Modify: `process/admin-pengembalian-edit.php`

- [ ] **Step 1: Stop calling stock reserve/release helpers from rental writes**
- [ ] **Step 2: Remove damaged-stock helpers and the `flagged` return path**
- [ ] **Step 3: Keep request validation and rental/return ownership checks intact**

### Task 4: Remove Damaged Return UI Paths

**Files:**
- Modify: `staff/returns.php`
- Modify: `staff/borrowings.php`
- Modify: `staff/index.php`
- Modify: `staff/reports.php`
- Modify: `admin/returns.php`
- Modify: `admin/activity-log.php`
- Modify: `admin/borrowings.php`
- Modify: `admin/products.php`
- Modify: `admin/index.php`
- Modify: `admin/categories.php`
- Modify: `admin/users.php`

- [ ] **Step 1: Remove damaged/flagged labels and actions**
- [ ] **Step 2: Keep completed return actions mapped to `completed` only**
- [ ] **Step 3: Update summaries that previously counted damaged returns**

### Task 5: Verify End To End

**Files:**
- Test: `tests/sql-trigger-stock-flow-test.sh`
- Test: `tests/rental-return-integrity-test.sh`
- Test: `tests/staff-stock-price-management-test.sh`

- [ ] **Step 1: Run the SQL trigger test**
- [ ] **Step 2: Run the rental/return flow regression test**
- [ ] **Step 3: Run the stock management regression test**
