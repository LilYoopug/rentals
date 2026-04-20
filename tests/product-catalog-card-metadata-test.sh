#!/usr/bin/env bash
set -euo pipefail

file="products.php"

if ! grep -q 'function getCategoryLabel(category)' "$file"; then
  echo 'Expected product catalog renderer to map product categories to display labels'
  exit 1
fi

if ! grep -q 'flex items-start justify-between gap-3' "$file"; then
  echo 'Expected product catalog cards to render brand and category in the same top row'
  exit 1
fi

if ! grep -q 'line-clamp-2 leading-6 text-sm text-neutral-400' "$file"; then
  echo 'Expected product catalog cards to render a truncated two-line description preview'
  exit 1
fi

if ! grep -q 'window.formatCurrencyIDR' "$file" || ! grep -q '/hari' "$file"; then
  echo 'Expected product catalog cards to render Indonesian Rupiah pricing and /hari copy'
  exit 1
fi

if ! grep -q 'flex flex-col items-start gap-1' "$file"; then
  echo 'Expected discounted catalog prices to stack the original struck-through price above the discounted price'
  exit 1
fi

if grep -q '\$/day' "$file"; then
  echo 'Expected product catalog cards to omit dollar per-day strings'
  exit 1
fi

echo 'OK: product catalog cards render category badges and truncated descriptions'
