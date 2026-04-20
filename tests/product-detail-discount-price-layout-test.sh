#!/usr/bin/env bash
set -euo pipefail

file="product-detail.php"

if ! grep -q 'flex flex-col items-start gap-1 mb-2' "$file"; then
  echo 'Expected product detail hero price block to stack the original struck-through price above the discounted price'
  exit 1
fi

if ! grep -q 'flex flex-col items-start gap-1' "$file"; then
  echo 'Expected related product cards to stack the original struck-through price above the discounted price'
  exit 1
fi

if grep -q 'line-through ml-2' "$file"; then
  echo 'Expected product detail discount pricing to stop placing the original price beside the discounted price'
  exit 1
fi

echo 'OK: product detail discounted prices use the stacked original-price layout'
