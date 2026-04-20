#!/usr/bin/env bash
set -euo pipefail

file="user/rentals.php"

for expected in \
  "all: { listId: 'all-rentals-list', emptyId: 'all-empty' }" \
  "aktif: { listId: 'active-rentals-list', emptyId: 'active-empty' }" \
  "menunggu: { listId: 'pending-rentals-list', emptyId: 'pending-empty' }" \
  "complete: { listId: 'complete-rentals-list', emptyId: 'complete-empty' }"; do
  if ! grep -q "${expected}" "$file"; then
    echo "Expected rentals page to define tab mapping entry: ${expected}"
    exit 1
  fi
done

for expected in \
  "all: 'tab-all'" \
  "aktif: 'tab-active'" \
  "menunggu: 'tab-pending'" \
  "complete: 'tab-complete'"; do
  if ! grep -q "${expected}" "$file"; then
    echo "Expected rentals page to define tab content mapping entry: ${expected}"
    exit 1
  fi
done

if grep -q 'document.getElementById(`\\${currentTab}-rentals-list`)' "$file"; then
  echo 'Expected rentals page to stop relying on brittle currentTab string interpolation for list containers'
  exit 1
fi

if grep -q 'document.getElementById(`\\${currentTab}-empty`)' "$file"; then
  echo 'Expected rentals page to stop relying on brittle currentTab string interpolation for empty states'
  exit 1
fi

if grep -q "classList.toggle('aktif'" "$file"; then
  echo "Expected rentals page tabs to stop toggling the non-existent aktif class"
  exit 1
fi

if ! grep -q "classList.toggle('active'" "$file"; then
  echo "Expected rentals page tabs to toggle the active class used by the page CSS"
  exit 1
fi

if ! grep -q '<body class="bg-neutral-950 text-neutral-100 min-h-screen flex flex-col">' "$file"; then
  echo 'Expected rentals page body to become a flex column for sticky footer behavior'
  exit 1
fi

if ! grep -q '<main class="pt-20 pb-12 px-6 flex-1">' "$file"; then
  echo 'Expected rentals page main content to grow and push the footer to the bottom'
  exit 1
fi

for message in \
  'Belum ada rental di akun Anda saat ini.' \
  'Belum ada rental dengan status aktif saat ini.' \
  'Belum ada rental dengan status menunggu saat ini.' \
  'Belum ada rental dengan status selesai atau dibatalkan saat ini.'; do
  if ! grep -q "$message" "$file"; then
    echo "Expected rentals page to include empty-state copy: ${message}"
    exit 1
  fi
done

echo 'OK: rentals page has explicit tab empty-state mapping and sticky footer layout'
