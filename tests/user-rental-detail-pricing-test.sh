#!/usr/bin/env bash
set -euo pipefail

file="user/rentals.php"

if grep -q 'line-through ml-1">${window.formatCurrencyIDR(rental.dailyRate)}/hari</span>' "$file"; then
  echo 'Expected rental details modal to stop using the discounted daily rate as the struck-through original price'
  exit 1
fi

if ! grep -q 'function getOriginalDailyRate(rental, productPrice = 0)' "$file" || ! grep -q 'const originalDailyRate = getOriginalDailyRate(rental, product.price);' "$file"; then
  echo 'Expected rental details modal to derive the original daily rate from the product price via the shared helper'
  exit 1
fi

if ! grep -q 'function getDiscountAmount(rental, productPrice = 0)' "$file" || ! grep -q 'const discountAmount = getDiscountAmount(rental, product.price);' "$file"; then
  echo 'Expected rental details modal to compute the discount amount from the shared helper'
  exit 1
fi

echo 'OK: rental details modal uses the correct original and discounted daily rates'
