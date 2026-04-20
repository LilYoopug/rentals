#!/usr/bin/env bash
set -euo pipefail

file="includes/functions.php"

if grep -q "'tbody > tr'" "$file"; then
  echo 'Expected shared entry motion to stop targeting table rows'
  exit 1
fi

if grep -q "'\\[id\\$=\"-list\"\\] > \\*'" "$file" || grep -q "'\\[id\\$=\"-list\"\\] > \*'" "$file"; then
  echo 'Expected shared entry motion to stop targeting generic list/card items'
  exit 1
fi

if ! grep -q '\[id\$="-pagination"\]' "$file"; then
  echo 'Expected shared motion runtime to explicitly skip pagination shells'
  exit 1
fi

if ! grep -q '\[id\$="-page-numbers"\]' "$file"; then
  echo 'Expected shared motion runtime to explicitly skip pagination number containers'
  exit 1
fi

echo 'OK: shared entry motion excludes rows, mobile cards, and pagination'
