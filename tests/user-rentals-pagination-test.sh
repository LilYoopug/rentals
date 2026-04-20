#!/usr/bin/env bash
set -euo pipefail

file="user/rentals.php"

for marker in \
  "let rentalsCurrentPage = 1;" \
  "const rentalsPerPage = 8;" \
  "function renderRentalsPagination()" \
  "function goToRentalsPage(page)" \
  "id=\"rentals-prev\"" \
  "id=\"rentals-page-numbers\"" \
  "id=\"rentals-next\"" \
  "id=\"rentals-shown\"" \
  "id=\"rentals-total\""; do
  if ! grep -q "$marker" "$file"; then
    echo "Expected rentals page to include pagination marker: $marker"
    exit 1
  fi
done

if ! grep -q "rentalsCurrentPage = 1;" "$file"; then
  echo "Expected rentals page to reset pagination when tab/filter changes"
  exit 1
fi

if ! grep -q "filtered.slice(start, end)" "$file"; then
  echo "Expected rentals page to render paginated slices of filtered rentals"
  exit 1
fi

echo "OK: rentals page defines shared pagination state and controls"
