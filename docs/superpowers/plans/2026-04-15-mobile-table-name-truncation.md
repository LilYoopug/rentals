# Mobile Table Name Truncation Implementation Plan

> **For agentic workers:** REQUIRED: Use superpowers:subagent-driven-development (if subagents available) or superpowers:executing-plans to implement this plan. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Ensure long name-like values in mobile table views render with ellipsis across admin and staff pages.

**Architecture:** Add one mobile-only helper class to each duplicated page family and apply it only to name-like text nodes inside mobile table cards and the stock-price product row. Verify through a shell test that representative rendered pages include the helper class in the expected markup.

**Tech Stack:** PHP, inline CSS, server-rendered HTML, JavaScript table templates, shell tests

---

## Chunk 1: Lock behavior with a failing test

### Task 1: Add regression test

**Files:**
- Create: `tests/mobile-table-name-truncation-test.sh`

- [ ] **Step 1: Write the failing test**
Create a shell test that starts the local test stack, logs in as admin and staff, fetches representative pages, and expects a `mobile-name-ellipsis` class on mobile name fields in `admin/users.php`, `staff/borrowings.php`, and `staff/stock-price.php`.

- [ ] **Step 2: Run test to verify it fails**
Run: `bash tests/mobile-table-name-truncation-test.sh`
Expected: FAIL because the helper class is not in the current markup.

## Chunk 2: Add mobile helper styles and apply them

### Task 2: Patch admin pages

**Files:**
- Modify: `admin/index.php`
- Modify: `admin/users.php`
- Modify: `admin/products.php`
- Modify: `admin/categories.php`
- Modify: `admin/borrowings.php`
- Modify: `admin/returns.php`
- Modify: `admin/activity-log.php`

- [ ] **Step 1: Add a mobile-only helper CSS class**
Add `.mobile-name-ellipsis` with ellipsis behavior under the mobile breakpoint near the existing table styles.

- [ ] **Step 2: Apply the helper to admin mobile table name fields**
Add the helper to user names, inventory names, borrowing customer/equipment values, and return customer/equipment values shown in mobile table cards.

### Task 3: Patch staff pages

**Files:**
- Modify: `staff/index.php`
- Modify: `staff/borrowings.php`
- Modify: `staff/returns.php`
- Modify: `staff/reports.php`
- Modify: `staff/stock-price.php`

- [ ] **Step 1: Add the helper CSS class to staff page styles**
Add the same `.mobile-name-ellipsis` rule in the staff page CSS blocks.

- [ ] **Step 2: Apply the helper to staff mobile table name fields**
Add it to customer/equipment values in mobile borrowings/returns cards and to the stock-price product name block.

## Chunk 3: Verify

### Task 4: Re-run the regression test

**Files:**
- Test: `tests/mobile-table-name-truncation-test.sh`

- [ ] **Step 1: Run the focused test**
Run: `bash tests/mobile-table-name-truncation-test.sh`
Expected: PASS.
