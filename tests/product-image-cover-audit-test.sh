#!/usr/bin/env bash
set -euo pipefail

PROJECT_ROOT="$(cd "$(dirname "$0")/.." && pwd)"

assert_contains() {
  local file="$1"
  local pattern="$2"
  local message="$3"

  if ! grep -Fq "$pattern" "$file"; then
    echo "$message"
    exit 1
  fi
}

assert_not_contains() {
  local file="$1"
  local pattern="$2"
  local message="$3"

  if grep -Fq "$pattern" "$file"; then
    echo "$message"
    exit 1
  fi
}

assert_contains "${PROJECT_ROOT}/products.php" 'class="w-full h-full object-cover transition-transform duration-500 hover:scale-105"' \
  'Expected product cards in products.php to use object-cover'

assert_contains "${PROJECT_ROOT}/product-detail.php" 'class="w-full h-full object-cover"' \
  'Expected product detail hero image to use object-cover'

assert_contains "${PROJECT_ROOT}/product-detail.php" 'class="w-full h-full object-cover transition-transform duration-500 hover:scale-105"' \
  'Expected related product cards in product-detail.php to use object-cover'

assert_contains "${PROJECT_ROOT}/user/rentals.php" 'id="modal-product-image" src="../images/gear-placeholder.svg" alt="" class="w-full h-full object-cover"' \
  'Expected rental details modal product image to use object-cover'

assert_contains "${PROJECT_ROOT}/user/rentals.php" 'id="return-product-image" src="../images/gear-placeholder.svg" alt="" class="w-full h-full object-cover"' \
  'Expected return modal product image to use object-cover'

assert_contains "${PROJECT_ROOT}/staff/stock-price.php" 'class="w-16 h-16 rounded-2xl object-cover bg-neutral-800 border border-neutral-700 shrink-0"' \
  'Expected staff stock-price product thumbnails to use object-cover'

assert_contains "${PROJECT_ROOT}/admin/products.php" 'object-fit: cover;' \
  'Expected admin product image helper styles to keep object-fit: cover'

assert_contains "${PROJECT_ROOT}/admin/products.php" '.inventory-preview-image {' \
  'Expected admin inventory preview image helper to exist'

assert_contains "${PROJECT_ROOT}/admin/products.php" '.detail-media-image {' \
  'Expected admin detail media image helper to exist'

if ! rg -n 'object-contain|object-fit:\s*contain' \
  "${PROJECT_ROOT}/admin" \
  "${PROJECT_ROOT}/petugas" \
  "${PROJECT_ROOT}/pelanggan" \
  "${PROJECT_ROOT}/products.php" \
  "${PROJECT_ROOT}/product-detail.php" \
  "${PROJECT_ROOT}/index.php" >/dev/null 2>&1; then
  :
else
  echo 'Expected product image surfaces to avoid object-contain / object-fit: contain'
  exit 1
fi

echo 'OK: audited product image surfaces stay on object-cover across representative pages'
