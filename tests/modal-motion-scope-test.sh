#!/usr/bin/env bash
set -euo pipefail

file="includes/functions.php"

if ! grep -q "target.matches('.modal-panel')" "$file"; then
  echo "Expected shared modal motion hook to target only .modal-panel elements directly"
  exit 1
fi

if grep -q '\[id\$=\\"-modal-content\\"\]' "$file"; then
  echo 'Expected shared modal motion hook to stop auto-animating local transition modal panels'
  exit 1
fi

if grep -q '\[id\$=\\"-modal\\"\]' "$file"; then
  echo 'Expected shared modal motion hook to stop auto-animating local transition modal backdrops'
  exit 1
fi

echo "OK: shared modal motion is scoped away from locally animated modal-content/modal backdrops"
