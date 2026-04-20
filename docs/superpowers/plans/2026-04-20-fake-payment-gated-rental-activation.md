# Fake Payment Gated Rental Activation Implementation Plan

> **For agentic workers:** REQUIRED: Use superpowers:subagent-driven-development (if subagents available) or superpowers:executing-plans to implement this plan. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Menambahkan gate fake payment setelah approval petugas sehingga rental baru menjadi `aktif` setelah pelanggan membayar dari halaman checkout internal.

**Architecture:** Perubahan dibagi menjadi empat lapisan. Pertama, perluas contract database dan helper status agar `disetujui` dan `payments` menjadi state resmi. Kedua, ubah flow backend approval dan payment agar approval membuat payment `pending` dan payment sukses memindahkan rental ke `aktif`. Ketiga, tambahkan halaman payment dan CTA di `Rental Saya` dengan shell dan UI proyek yang sama, tanpa floating nav. Keempat, perbarui surface staff/admin yang masih mengasumsikan approval langsung berarti `aktif`.

**Tech Stack:** PHP, MySQL/MariaDB, inline JavaScript, Tailwind CDN classes, shell-based regression tests

**Note:** Workspace saat ini tidak memiliki `.git`, jadi langkah commit tidak dituliskan sebagai requirement eksekusi lokal. Jika implementasi dipindahkan ke repo git yang sebenarnya, buat commit kecil di akhir tiap task.

---

## File Map

- Create: `data/payments-data.php`
- Create: `process/rental-payment-process.php`
- Create: `user/payment.php`
- Create: `tests/payment-gated-activation-test.sh`
- Create: `tests/user-payment-page-access-test.sh`
- Create: `tests/user-payment-page-shell-test.sh`
- Create: `tests/user-rentals-payment-cta-test.sh`
- Modify: `database/lenscraft.sql`
- Modify: `includes/functions.php`
- Modify: `data/rentals-data.php`
- Modify: `data/users/staff-data.php`
- Modify: `process/staff-peminjaman-approve.php`
- Modify: `user/rentals.php`
- Modify: `staff/borrowings.php`
- Modify: `staff/index.php`
- Modify: `staff/reports.php`
- Modify: `staff/returns.php`
- Modify: `admin/borrowings.php`
- Modify: `admin/index.php`
- Modify: `admin/products.php`
- Modify: `admin/categories.php`
- Modify: `admin/users.php`
- Modify: `admin/returns.php`
- Modify: `includes/staff-report-export.php`
- Modify: `tests/status-presenter-normalization-test.sh`
- Modify: `tests/sql-trigger-stock-flow-test.sh`
- Modify: `tests/staff-approval-sort-order-test.sh`
- Modify: `tests/staff-borrowings-admin-sync-test.sh`
- Modify: `tests/rental-return-integrity-test.sh`

## Chunk 1: Database Contract and Status Helpers

### Task 1: Add failing tests for the new status and payment contract

**Files:**
- Create: `tests/payment-gated-activation-test.sh`
- Create: `tests/user-payment-page-access-test.sh`
- Create: `tests/user-payment-page-shell-test.sh`
- Modify: `tests/status-presenter-normalization-test.sh`
- Modify: `tests/sql-trigger-stock-flow-test.sh`

- [ ] **Step 1: Extend the status presenter test to describe `disetujui`**

Add assertions in `tests/status-presenter-normalization-test.sh` for:
- `normalize_rental_status_value('disetujui')`
- `present_borrowing_workflow_status('disetujui')`
- any compatibility mapping that still needs to distinguish approved-before-payment from active rentals

- [ ] **Step 2: Extend the stock trigger test to describe reserved stock for `disetujui`**

Update `tests/sql-trigger-stock-flow-test.sh` so it inserts or updates a rental into `disetujui` and expects stock to remain reserved until the rental leaves the reserved states.

- [ ] **Step 3: Write a failing end-to-end approval/payment test**

Create `tests/payment-gated-activation-test.sh` to verify:
- staff approval changes rental `menunggu -> disetujui`
- one `payments` row is created as `pending`
- fake payment submit changes payment `pending -> paid`
- rental changes `disetujui -> aktif`

- [ ] **Step 4: Write failing access and shell tests for the new payment page**

Create:
- `tests/user-payment-page-access-test.sh` for owner-only access and blocked states
- `tests/user-payment-page-shell-test.sh` for navbar/footer presence and absence of floating nav

- [ ] **Step 5: Run the new and updated tests to verify failure**

Run:
- `bash tests/status-presenter-normalization-test.sh`
- `bash tests/sql-trigger-stock-flow-test.sh`
- `bash tests/payment-gated-activation-test.sh`
- `bash tests/user-payment-page-access-test.sh`
- `bash tests/user-payment-page-shell-test.sh`

Expected:
- presenter test fails because `disetujui` is not recognized yet
- trigger test fails because `disetujui` is not treated as stock-reserving yet
- payment tests fail because `payments` table, page, and process do not exist yet

### Task 2: Implement schema and helper support for `disetujui` and `payments`

**Files:**
- Modify: `database/lenscraft.sql`
- Modify: `includes/functions.php`
- Modify: `data/rentals-data.php`
- Create: `data/payments-data.php`
- Modify: `data/users/staff-data.php`

- [ ] **Step 1: Add the `payments` table and extend rental status enum**

In `database/lenscraft.sql`:
- add `disetujui` to the `rentals.status` enum
- create the `payments` table with one-row-per-rental uniqueness
- keep schema minimal: no extra attempt/history table

- [ ] **Step 2: Update rental stock triggers for `disetujui`**

Modify the `rentals` triggers so `disetujui` is treated like `menunggu`, `mendatang`, and `aktif` for stock reservation.

- [ ] **Step 3: Update shared normalization and presentation helpers**

In `includes/functions.php`:
- recognize `disetujui` as a first-class rental status
- keep backward-compatible English normalization where already supported
- define how `present_borrowing_workflow_status()` should present `disetujui` for staff/admin/customer UIs

- [ ] **Step 4: Update the rental data layer and add payment data helpers**

In `data/rentals-data.php` and new `data/payments-data.php`:
- expose payment state alongside rental data where needed
- add helpers to create pending payment records
- add helpers to fetch payment by rental and mark payment as paid

- [ ] **Step 5: Update staff borrowing sort order**

In `data/users/staff-data.php`, ensure `disetujui` sorts after true approval-ready items but before completed/rejected records, matching the intended borrowings queue.

- [ ] **Step 6: Run the schema/helper tests again**

Run:
- `bash tests/status-presenter-normalization-test.sh`
- `bash tests/sql-trigger-stock-flow-test.sh`

Expected: PASS

## Chunk 2: Backend Approval and Payment Flow

### Task 3: Change staff approval to create a pending payment instead of activating the rental

**Files:**
- Modify: `process/staff-peminjaman-approve.php`
- Modify: `data/rentals-data.php`
- Modify: `data/payments-data.php`

- [ ] **Step 1: Make approval transition only from `menunggu` to `disetujui`**

Update `process/staff-peminjaman-approve.php` so it:
- refuses invalid or already-processed rental states
- sets `approved_at`
- leaves the rental non-active

- [ ] **Step 2: Create or ensure a pending payment row during approval**

Use `data/payments-data.php` to create a single pending payment contract for the approved rental.

- [ ] **Step 3: Verify the approval half of the flow**

Run:
- `bash tests/payment-gated-activation-test.sh`

Expected: FAIL later in the flow at payment submission, but PASS through the approval and pending-payment assertions

### Task 4: Add the fake payment process endpoint with duplicate and ownership protection

**Files:**
- Create: `process/rental-payment-process.php`
- Modify: `data/payments-data.php`
- Modify: `data/rentals-data.php`

- [ ] **Step 1: Write the minimal payment submit endpoint**

Create `process/rental-payment-process.php` to:
- require logged-in customer + valid CSRF
- validate rental ownership
- validate rental is still `disetujui`
- validate payment is still `pending`
- validate required form fields

- [ ] **Step 2: Mark payment as paid and activate the rental**

Use the payment/rental data helpers so the process:
- updates payment `pending -> paid`
- stores fake payer and reference data
- updates rental `disetujui -> aktif`

- [ ] **Step 3: Refuse duplicate or invalid payment attempts**

Ensure the endpoint returns a clear error for:
- already-paid rentals
- rentals not owned by the current user
- rentals in `menunggu`, `ditolak`, `dibatalkan`, `aktif`, or `selesai`

- [ ] **Step 4: Run backend flow regressions**

Run:
- `bash tests/payment-gated-activation-test.sh`
- `bash tests/rental-return-integrity-test.sh`

Expected: PASS

## Chunk 3: Customer Rental CTA and Payment Page

### Task 5: Expose payment-aware status and CTA in `Rental Saya`

**Files:**
- Modify: `user/rentals.php`
- Modify: `data/rentals-data.php`
- Modify: `data/payments-data.php`
- Create: `tests/user-rentals-payment-cta-test.sh`

- [ ] **Step 1: Surface payment data inside the rentals payload**

Update the rental payload used by `user/rentals.php` so each row can tell whether it is:
- unpaid approved (`disetujui` + `pending`)
- paid active
- not payable

- [ ] **Step 2: Add the `Bayar Sekarang` action for approved unpaid rows**

In `user/rentals.php`:
- show `Bayar Sekarang` on approved unpaid rows
- keep active rentals on the existing detail/return actions
- update badges so `disetujui` is visually distinct from `menunggu`

- [ ] **Step 3: Update or add UI contract coverage**

Create or update tests so the rentals UI contract checks:
- approved unpaid rows render `Bayar Sekarang`
- already-active rows do not show pay CTA

- [ ] **Step 4: Run the targeted rentals tests**

Run:
- `bash tests/user-rentals-payment-cta-test.sh`
- `bash tests/user-rental-detail-pricing-test.sh`

Expected: PASS

### Task 6: Build the customer payment page using the existing LensCraft shell and UI language

**Files:**
- Create: `user/payment.php`
- Create: `tests/user-payment-page-shell-test.sh`
- Create: `tests/user-payment-page-access-test.sh`

- [ ] **Step 1: Build the page shell**

Create `user/payment.php` with:
- the same top navbar pattern as customer pages
- the same footer pattern
- no floating nav markup

- [ ] **Step 2: Build the main content from existing UI blocks**

Reuse the project’s existing UI language for:
- page header
- rental summary card
- payment method card
- payment form card
- total and CTA card

Do not introduce a new checkout-only visual system.

- [ ] **Step 3: Wire the page to the fake payment endpoint**

Submit the form to `process/rental-payment-process.php` with clear error and success states.

- [ ] **Step 4: Run the payment page access and shell tests**

Run:
- `bash tests/user-payment-page-access-test.sh`
- `bash tests/user-payment-page-shell-test.sh`

Expected: PASS

## Chunk 4: Staff/Admin Borrowing Surfaces

### Task 7: Update the primary borrowings pages to show the new approved-before-payment state cleanly

**Files:**
- Modify: `staff/borrowings.php`
- Modify: `admin/borrowings.php`
- Modify: `tests/staff-approval-sort-order-test.sh`
- Modify: `tests/staff-borrowings-admin-sync-test.sh`

- [ ] **Step 1: Update the page-level labels, filters, and badge logic**

On both borrowings pages:
- add a visible `disetujui` state for records awaiting payment
- keep `aktif` reserved for post-payment rentals
- update filter options and display labels accordingly

- [ ] **Step 2: Adjust any JS payload assumptions**

If the pages still collapse all approved-like states into `approved`, split them so unpaid approved rows do not masquerade as active rentals.

- [ ] **Step 3: Update the existing staff/admin sync and sort-order tests**

Make the test fixtures and expectations cover:
- unpaid approved rows
- active rows
- correct ordering in staff queues

- [ ] **Step 4: Run the staff/admin borrowings regressions**

Run:
- `bash tests/staff-approval-sort-order-test.sh`
- `bash tests/staff-borrowings-admin-sync-test.sh`

Expected: PASS

### Task 8: Propagate `disetujui` through dashboard, report, and export surfaces

**Files:**
- Modify: `staff/index.php`
- Modify: `staff/reports.php`
- Modify: `staff/returns.php`
- Modify: `admin/index.php`
- Modify: `admin/products.php`
- Modify: `admin/categories.php`
- Modify: `admin/users.php`
- Modify: `admin/returns.php`
- Modify: `includes/staff-report-export.php`

- [ ] **Step 1: Replace raw status assumptions in summary metrics**

Update counts and percentages so:
- `disetujui` is no longer counted as “menunggu approval”
- `aktif` remains the only truly active rental state
- approved-before-payment records are labeled correctly in cards and tables

- [ ] **Step 2: Update report/export labels and any hardcoded English `approved` strings**

Make exports and reports use the new state consistently so staff-facing outputs do not conflate unpaid approved rentals with active ones.

- [ ] **Step 3: Run representative dashboard/report regressions**

Run:
- `bash tests/staff-report-export-test.sh`
- `bash tests/staff-reports-summary-filter-test.sh`

Expected: PASS

## Chunk 5: Final Verification

### Task 9: Run the full regression pack for the payment-gated activation flow

**Files:**
- Modify: none unless failures appear

- [ ] **Step 1: Run the targeted end-to-end and contract tests**

Run:
- `bash tests/status-presenter-normalization-test.sh`
- `bash tests/sql-trigger-stock-flow-test.sh`
- `bash tests/payment-gated-activation-test.sh`
- `bash tests/user-payment-page-access-test.sh`
- `bash tests/user-payment-page-shell-test.sh`
- `bash tests/user-rentals-payment-cta-test.sh`
- `bash tests/rental-return-integrity-test.sh`
- `bash tests/staff-approval-sort-order-test.sh`
- `bash tests/staff-borrowings-admin-sync-test.sh`
- `bash tests/staff-report-export-test.sh`
- `bash tests/staff-reports-summary-filter-test.sh`

- [ ] **Step 2: Do one final codebase search for stale assumptions**

Run:
- `rg -n "approved|aktif|menunggu|disetujui|payment|Bayar Sekarang" admin staff user data includes process tests`

Expected:
- no stale places where unpaid approved rentals are still treated as active by mistake
- no missing payment references in the new flow files

- [ ] **Step 3: Fix any remaining regressions and rerun only the affected tests**

Keep the reruns narrow. Do not expand scope into unrelated UI cleanup or refactors.

---

Plan complete and saved to `docs/superpowers/plans/2026-04-20-fake-payment-gated-rental-activation.md`. Ready to execute?
