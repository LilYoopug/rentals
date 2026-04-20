# Admin/Staff Shared Shell Route Split Implementation Plan

> **For agentic workers:** REQUIRED: Use superpowers:subagent-driven-development (if subagents available) or superpowers:executing-plans to implement this plan. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Memecah halaman admin dan staff yang gemuk menjadi shared shell per role dengan route yang hanya merender section miliknya masing-masing.

**Architecture:** Layout bersama untuk `admin` dan `staff` dipindahkan ke shell/partial shared. Setiap route akan memuat satu partial konten dan hanya data/JS yang dibutuhkan route itu. URL tetap sama supaya tidak ada perubahan kontrak ke user.

**Tech Stack:** PHP, inline JavaScript, shell-based route tests

---

## File Map

### Admin
- Create: `admin/_shared/shell.php`
- Create: `admin/_shared/nav.php`
- Create: `admin/_shared/footer.php`
- Create: `admin/_sections/overview.php`
- Create: `admin/_sections/users.php`
- Create: `admin/_sections/categories.php`
- Create: `admin/_sections/products.php`
- Create: `admin/_sections/borrowings.php`
- Create: `admin/_sections/returns.php`
- Create: `admin/_sections/activity-log.php`
- Modify: `admin/index.php`
- Modify: `admin/users.php`
- Modify: `admin/categories.php`
- Modify: `admin/products.php`
- Modify: `admin/borrowings.php`
- Modify: `admin/returns.php`
- Modify: `admin/activity-log.php`

### Staff
- Create: `staff/_shared/shell.php`
- Create: `staff/_shared/nav.php`
- Create: `staff/_shared/footer.php`
- Create: `staff/_sections/overview.php`
- Create: `staff/_sections/borrowings.php`
- Create: `staff/_sections/returns.php`
- Create: `staff/_sections/reports.php`
- Create: `staff/_sections/stock-price.php`
- Modify: `staff/index.php`
- Modify: `staff/borrowings.php`
- Modify: `staff/returns.php`
- Modify: `staff/reports.php`
- Modify: `staff/stock-price.php`

### Tests
- Create: `tests/admin-route-single-section-test.sh`
- Create: `tests/staff-route-single-section-test.sh`
- Modify: existing route/content tests if exact strings move

## Chunk 1: Route Regression Harness

### Task 1: Add failing route-scope tests

**Files:**
- Create: `tests/admin-route-single-section-test.sh`
- Create: `tests/staff-route-single-section-test.sh`

- [ ] **Step 1: Write failing tests**

Admin test should verify:
- each admin route returns 200
- each page contains its own section heading
- each page no longer contains unrelated hidden `content-section` blocks

Staff test should verify the same for staff routes.

- [ ] **Step 2: Run tests to verify failure**

Run:
- `bash tests/admin-route-single-section-test.sh`
- `bash tests/staff-route-single-section-test.sh`

Expected: FAIL on current bloated multi-section files

## Chunk 2: Shared Shell Extraction

### Task 2: Build admin shared shell

**Files:**
- Create: `admin/_shared/shell.php`
- Create: `admin/_shared/nav.php`
- Create: `admin/_shared/footer.php`

- [ ] **Step 1: Extract repeated admin chrome**
- [ ] **Step 2: Keep route-specific content injection simple**
- [ ] **Step 3: Run syntax checks**

Run:
- `php -l admin/_shared/shell.php`
- `php -l admin/_shared/nav.php`
- `php -l admin/_shared/footer.php`

### Task 3: Build staff shared shell

**Files:**
- Create: `staff/_shared/shell.php`
- Create: `staff/_shared/nav.php`
- Create: `staff/_shared/footer.php`

- [ ] **Step 1: Extract repeated staff chrome**
- [ ] **Step 2: Run syntax checks**

Run:
- `php -l staff/_shared/shell.php`
- `php -l staff/_shared/nav.php`
- `php -l staff/_shared/footer.php`

## Chunk 3: Admin Route Split

### Task 4: Split admin overview

**Files:**
- Create: `admin/_sections/overview.php`
- Modify: `admin/index.php`

- [ ] **Step 1: Write/adjust failing admin route test for overview**
- [ ] **Step 2: Move overview-only content/data into partial**
- [ ] **Step 3: Make `admin/index.php` render only overview**
- [ ] **Step 4: Run tests**

Run:
- `bash tests/admin-dashboard-route-test.sh`
- `bash tests/admin-route-single-section-test.sh`

### Task 5: Split admin users

**Files:**
- Create: `admin/_sections/users.php`
- Modify: `admin/users.php`

- [ ] **Step 1: Move users-only data and UI**
- [ ] **Step 2: Remove unrelated sections from route**
- [ ] **Step 3: Run tests**

Run:
- `bash tests/admin-route-single-section-test.sh`

### Task 6: Split admin categories

**Files:**
- Create: `admin/_sections/categories.php`
- Modify: `admin/categories.php`

- [ ] **Step 1: Move categories-only data and UI**
- [ ] **Step 2: Run tests**

### Task 7: Split admin products

**Files:**
- Create: `admin/_sections/products.php`
- Modify: `admin/products.php`

- [ ] **Step 1: Move products-only data and UI**
- [ ] **Step 2: Run tests**

### Task 8: Split admin borrowings

**Files:**
- Create: `admin/_sections/borrowings.php`
- Modify: `admin/borrowings.php`

- [ ] **Step 1: Move borrowings-only data and UI**
- [ ] **Step 2: Preserve route-specific tests**

Run:
- `bash tests/admin-borrowings-filter-button-test.sh`
- `bash tests/admin-route-single-section-test.sh`

### Task 9: Split admin returns

**Files:**
- Create: `admin/_sections/returns.php`
- Modify: `admin/returns.php`

- [ ] **Step 1: Move returns-only data and UI**
- [ ] **Step 2: Run tests**

### Task 10: Split admin activity log

**Files:**
- Create: `admin/_sections/activity-log.php`
- Modify: `admin/activity-log.php`

- [ ] **Step 1: Move activity-log-only data and UI**
- [ ] **Step 2: Run tests**

## Chunk 4: Staff Route Split

### Task 11: Split staff overview

**Files:**
- Create: `staff/_sections/overview.php`
- Modify: `staff/index.php`

- [ ] **Step 1: Move dashboard-only content**
- [ ] **Step 2: Run tests**

### Task 12: Split staff borrowings

**Files:**
- Create: `staff/_sections/borrowings.php`
- Modify: `staff/borrowings.php`

- [ ] **Step 1: Move borrowings-only content**
- [ ] **Step 2: Run tests**

### Task 13: Split staff returns

**Files:**
- Create: `staff/_sections/returns.php`
- Modify: `staff/returns.php`

- [ ] **Step 1: Move returns-only content**
- [ ] **Step 2: Run tests**

### Task 14: Split staff reports

**Files:**
- Create: `staff/_sections/reports.php`
- Modify: `staff/reports.php`

- [ ] **Step 1: Move reports-only content**
- [ ] **Step 2: Run tests**

### Task 15: Align staff stock-price with shell

**Files:**
- Create: `staff/_sections/stock-price.php`
- Modify: `staff/stock-price.php`

- [ ] **Step 1: Keep current focused content but move into shared shell architecture**
- [ ] **Step 2: Run tests**

Run:
- `bash tests/staff-stock-price-management-test.sh`

## Chunk 5: Final Verification

### Task 16: Run full representative verification

**Files:**
- Modify: as needed if failures appear

- [ ] **Step 1: Run final route and regression pack**

Run:
- `bash tests/admin-route-single-section-test.sh`
- `bash tests/staff-route-single-section-test.sh`
- `bash tests/admin-dashboard-route-test.sh`
- `bash tests/admin-borrowings-filter-button-test.sh`
- `bash tests/staff-stock-price-management-test.sh`
- `bash tests/staff-report-export-test.sh`
- `bash tests/customer-guards-and-session-test.sh`

- [ ] **Step 2: Run syntax sweep**

Run:
- `find admin staff -name '*.php' -print0 | xargs -0 -n1 php -l`

- [ ] **Step 3: Fix remaining drift and rerun affected tests**

---

Plan complete and saved to `docs/superpowers/plans/2026-04-19-admin-staff-shared-shell-route-split.md`. Ready to execute?
