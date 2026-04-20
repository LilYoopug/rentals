#!/usr/bin/env bash
set -euo pipefail

admin_files=(
  "admin/borrowings.php"
  "admin/products.php"
  "admin/categories.php"
  "admin/users.php"
  "admin/returns.php"
)

staff_files=(
  "staff/borrowings.php"
  "staff/returns.php"
  "staff/index.php"
)

for file in "${admin_files[@]}"; do
  if ! rg -q 'padding-top:\s*1rem;' "$file"; then
    echo "Expected ${file} modal action spacing to use at least 1rem top padding"
    exit 1
  fi

  if ! rg -q 'border-top:\s*1px solid rgba\(255, 255, 255, 0\.06\);' "$file"; then
    echo "Expected ${file} modal action spacing to include a separating top border"
    exit 1
  fi
done

for file in "${staff_files[@]}"; do
  if ! rg -q 'padding-top:\s*1rem;' "$file"; then
    echo "Expected ${file} action-sheet footer spacing to use at least 1rem top padding"
    exit 1
  fi

  if ! rg -q 'border-top:\s*1px solid rgba\(255, 255, 255, 0\.06\);' "$file"; then
    echo "Expected ${file} action-sheet footer spacing to include a separating top border"
    exit 1
  fi
done

if ! awk '
  /modal-actions/ { window = 20 }
  window > 0 && /Save Peminjaman/ { found = 1 }
  window > 0 { window-- }
  END { exit(found ? 0 : 1) }
' admin/borrowings.php; then
  echo 'Expected the admin borrowings modal to wrap Save Peminjaman actions in the shared modal-actions footer'
  exit 1
fi

echo 'OK: modal action footers have deliberate spacing from form content'
