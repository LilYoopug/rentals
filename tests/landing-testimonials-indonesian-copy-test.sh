#!/usr/bin/env bash
set -euo pipefail

source "$(cd "$(dirname "$0")/.." && pwd)/tests/helpers/test-env.sh"

start_test_stack

page_body="$(mktemp)"
trap 'rm -f "${page_body}"' EXIT

curl -sS "${TEST_BASE_URL}/index.php" -o "${page_body}"

for removed_copy in \
  'What Photographers Say' \
  'LensCraft saved my wedding shoot when my camera died.' \
  'Event Photographer' \
  'Commercial Shooter' \
  'Content Creator'
do
  if grep -q "${removed_copy}" "${page_body}"; then
    echo "Expected landing page testimonials to remove English copy: ${removed_copy}"
    exit 1
  fi
done

echo 'OK: landing page testimonial copy is fully Indonesian'
