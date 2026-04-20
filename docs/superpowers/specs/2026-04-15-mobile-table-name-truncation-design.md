# Mobile Table Name Truncation Design

- Scope: mobile-only truncation for name-like table fields (user/customer/product/equipment/category names).
- Approach: add a reusable mobile helper class and apply it only to name wrappers in admin/staff table templates and stock-price rows.
- Safety: avoid truncating IDs, dates, prices, totals, and status badges.
- Verification: shell test checks rendered markup includes helper class on representative admin/staff pages.
