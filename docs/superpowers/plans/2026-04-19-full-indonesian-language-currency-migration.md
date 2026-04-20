# Full Indonesian Language and Currency Migration Implementation Plan

> **For agentic workers:** REQUIRED: Use superpowers:subagent-driven-development (if subagents available) or superpowers:executing-plans to implement this plan. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Mengubah seluruh proyek ke Bahasa Indonesia penuh, termasuk teks UI, format Rupiah, schema SQL, seed data, dan nilai bisnis internal.

**Architecture:** Rollout dilakukan bertahap. Mulai dari helper kompatibilitas dan format Rupiah, lalu migrasi logika PHP/JS, kemudian schema dan seed SQL, lalu perbarui semua test agar memverifikasi nilai Indonesia. Selama rollout, normalisasi menerima nilai lama dan baru agar aplikasi tidak putus di tengah.

**Tech Stack:** PHP, MySQL/MariaDB, SQL schema/seed, JavaScript inline, shell-based regression tests

---

## File Map

- Modify: `includes/functions.php`
- Modify: `data/categories-data.php`
- Modify: `data/products-data.php`
- Modify: `data/rentals-data.php`
- Modify: `data/returns-data.php`
- Modify: `data/users/customer-data.php`
- Modify: `data/users/admin-data.php`
- Modify: `data/users/staff-data.php`
- Modify: `process/*.php` yang membandingkan role/status/method
- Modify: `index.php`, `products.php`, `product-detail.php`, `login.php`, `register.php`, `forgot-password.php`, `terms.php`, `privacy.php`
- Modify: `user/*.php`, `admin/*.php`, `staff/*.php`
- Modify: `includes/staff-report-export.php`
- Modify: `database/lenscraft.sql`
- Modify: `database/seed-lenscraft.sql`
- Modify: `tests/*.sh` yang mengasumsikan nilai Inggris atau currency dolar
- Create: `tests/indonesian-runtime-normalization-test.sh`
- Create: `tests/indonesian-schema-seed-test.sh`

## Chunk 1: Helper Normalisasi dan Rupiah

### Task 1: Tambah regression test helper bahasa/currency

**Files:**
- Create: `tests/indonesian-runtime-normalization-test.sh`
- Modify: `includes/functions.php`

- [ ] **Step 1: Write the failing test**

Tambahkan test yang memverifikasi:
- format currency menggunakan `Rp`
- helper normalisasi menerima nilai Inggris dan memetakan ke Indonesia

- [ ] **Step 2: Run test to verify it fails**

Run: `bash tests/indonesian-runtime-normalization-test.sh`
Expected: FAIL karena helper belum ada / output masih dolar

- [ ] **Step 3: Write minimal implementation**

Tambahkan di `includes/functions.php`:
- formatter Rupiah terpusat
- normalizer untuk role, status, metode pengiriman, category slug bila perlu

- [ ] **Step 4: Run test to verify it passes**

Run: `bash tests/indonesian-runtime-normalization-test.sh`
Expected: PASS

## Chunk 2: Migrasi Logika Data dan Process

### Task 2: Ubah layer `data/` agar menghasilkan nilai Indonesia

**Files:**
- Modify: `data/categories-data.php`
- Modify: `data/products-data.php`
- Modify: `data/rentals-data.php`
- Modify: `data/returns-data.php`
- Modify: `data/users/customer-data.php`
- Modify: `data/users/admin-data.php`
- Modify: `data/users/staff-data.php`

- [ ] **Step 1: Write failing/updated tests for status dan method**

Gunakan test yang sudah ada dan tambahkan assertion Indonesia bila perlu.

- [ ] **Step 2: Run targeted tests to verify failure**

Run:
- `bash tests/customer-guards-and-session-test.sh`
- `bash tests/rental-return-integrity-test.sh`
- `bash tests/sql-trigger-stock-flow-test.sh`

Expected: FAIL pada nilai/status lama

- [ ] **Step 3: Update PHP data logic**

Ubah:
- perbandingan role/status/method
- payload normalize/denormalize
- label fallback

- [ ] **Step 4: Run targeted tests to verify pass**

Run command yang sama.

### Task 3: Ubah process flow ke nilai Indonesia

**Files:**
- Modify: `process/login-process.php`
- Modify: `process/register-process.php`
- Modify: `process/rental-create-process.php`
- Modify: `process/rental-return-process.php`
- Modify: `process/staff-*.php`
- Modify: `process/admin-*.php`

- [ ] **Step 1: Update redirects/guards/status writes**
- [ ] **Step 2: Run related auth/rental tests**

Run:
- `bash tests/demo-auth-flow-test.sh`
- `bash tests/customer-guards-and-session-test.sh`
- `bash tests/rental-return-integrity-test.sh`

## Chunk 3: UI Copy dan JavaScript Currency

### Task 4: Ganti semua copy user-facing ke Indonesia

**Files:**
- Modify: halaman root, `user/*.php`, `admin/*.php`, `staff/*.php`

- [ ] **Step 1: Search all remaining English copy**

Run:
`rg -n "Profile|Security|Appearance|Privacy|Save|Cancel|Borrowing|Return|Settings|Revenue|Reports|Dashboard|Search|Filter|Logged in|Download|Today|This Week|This Month|Active|Rejected|Approved|Pending|Completed" . --glob '!uploads/**' --glob '!*.docx'`

- [ ] **Step 2: Replace copy with Indonesian equivalents**

- [ ] **Step 3: Update JS labels/tooltips/alerts**

- [ ] **Step 4: Run representative UI regressions**

Run:
- `bash tests/user-settings-single-page-test.sh`
- `bash tests/admin-borrowings-filter-button-test.sh`
- `bash tests/mobile-table-name-truncation-test.sh`

### Task 5: Ganti semua rendering dolar ke Rupiah

**Files:**
- Modify: `product-detail.php`
- Modify: `user/rentals.php`
- Modify: `staff/*.php`
- Modify: `admin/*.php`
- Modify: `includes/staff-report-export.php`

- [ ] **Step 1: Find raw `$${...}` and dollar formatting**

Run:
`rg -n "\\$\\$|\\$\\{|Revenue \\(\\$\\)|\\$0\\.00|/day" . --glob '!uploads/**'`

- [ ] **Step 2: Replace with helper/function Rupiah**

- [ ] **Step 3: Run currency-focused tests**

Run:
- `bash tests/staff-report-export-test.sh`
- `bash tests/staff-reports-summary-filter-test.sh`

## Chunk 4: Schema dan Seed Indonesia

### Task 6: Ubah schema SQL ke enum/nilai Indonesia

**Files:**
- Modify: `database/lenscraft.sql`

- [ ] **Step 1: Write failing schema/seed test**

Create `tests/indonesian-schema-seed-test.sh` untuk memeriksa enum/schema/seed Indonesia.

- [ ] **Step 2: Run test to verify failure**

Run: `bash tests/indonesian-schema-seed-test.sh`
Expected: FAIL

- [ ] **Step 3: Migrate schema**

Ubah enum dan trigger SQL ke istilah Indonesia.

- [ ] **Step 4: Run test to verify pass**

Run: `bash tests/indonesian-schema-seed-test.sh`

### Task 7: Ubah seed data ke Indonesia penuh

**Files:**
- Modify: `database/seed-lenscraft.sql`

- [ ] **Step 1: Translate categories, statuses, methods, demo text, currency-relevant values**
- [ ] **Step 2: Ensure seeded usernames/identifiers follow keputusan migrasi**
- [ ] **Step 3: Re-run schema/seed test and auth/demo tests**

Run:
- `bash tests/indonesian-schema-seed-test.sh`
- `bash tests/demo-auth-flow-test.sh`

## Chunk 5: Test Suite Alignment

### Task 8: Update shell tests to Indonesian expectations

**Files:**
- Modify: affected `tests/*.sh`

- [ ] **Step 1: Search for old English values and dollar expectations**

Run:
`rg -n "active|inactive|pending|completed|cancelled|rejected|pickup|delivery|\\$|Revenue|Active|Pending|Approved|Rejected|Completed" tests`

- [ ] **Step 2: Update expectations**

- [ ] **Step 3: Run full targeted regression pack**

Run:
- `bash tests/demo-auth-flow-test.sh`
- `bash tests/customer-guards-and-session-test.sh`
- `bash tests/password-reset-flow-test.sh`
- `bash tests/rental-return-integrity-test.sh`
- `bash tests/sql-trigger-stock-flow-test.sh`
- `bash tests/staff-report-export-test.sh`
- `bash tests/staff-stock-price-management-test.sh`
- `bash tests/staff-reports-summary-filter-test.sh`
- `bash tests/user-settings-single-page-test.sh`

## Chunk 6: Final Verification

### Task 9: Run final representative verification

**Files:**
- Modify: none unless failures appear

- [ ] **Step 1: Run final regression command set**

Run:
- `bash tests/demo-auth-flow-test.sh`
- `bash tests/customer-guards-and-session-test.sh`
- `bash tests/password-reset-flow-test.sh`
- `bash tests/rental-return-integrity-test.sh`
- `bash tests/sql-trigger-stock-flow-test.sh`
- `bash tests/staff-report-export-test.sh`
- `bash tests/staff-stock-price-management-test.sh`
- `bash tests/staff-reports-summary-filter-test.sh`
- `bash tests/user-settings-single-page-test.sh`
- `bash tests/admin-dashboard-route-test.sh`

- [ ] **Step 2: Search for stragglers**

Run:
- `rg -n "Profile|Security|Appearance|Privacy|Save|Cancel|Borrowing|Return|Settings|Revenue|Reports|Dashboard|Search|Filter|Logged in|Download|Today|This Week|This Month|Active|Rejected|Approved|Pending|Completed|\\$\\$|\\$\\{" . --glob '!uploads/**' --glob '!*.docx'`

- [ ] **Step 3: Fix any remaining gaps and rerun affected tests**

---

Plan complete and saved to `docs/superpowers/plans/2026-04-19-full-indonesian-language-currency-migration.md`. Ready to execute?
