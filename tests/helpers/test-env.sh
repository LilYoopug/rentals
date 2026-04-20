#!/usr/bin/env bash
set -euo pipefail

PROJECT_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
TEST_TMPDIR="$(mktemp -d)"
TEST_DB_DIR="$TEST_TMPDIR/db"
TEST_DB_SOCKET="$TEST_TMPDIR/mysql.sock"
TEST_DB_PID="$TEST_TMPDIR/mysql.pid"
TEST_DB_LOG="$TEST_TMPDIR/mysql.log"
TEST_APP_LOG="$TEST_TMPDIR/php-server.log"
TEST_DB_PORT="${TEST_DB_PORT:-$((3307 + (RANDOM % 2000)))}"
TEST_APP_PORT="${TEST_APP_PORT:-$((18080 + (RANDOM % 2000)))}"
TEST_BASE_URL="http://127.0.0.1:${TEST_APP_PORT}"

cleanup_test_stack() {
  if [[ -n "${TEST_APP_PID:-}" ]] && kill -0 "${TEST_APP_PID}" 2>/dev/null; then
    kill "${TEST_APP_PID}" 2>/dev/null || true
    wait "${TEST_APP_PID}" 2>/dev/null || true
  fi

  if [[ -n "${TEST_DB_SERVER_PID:-}" ]] && kill -0 "${TEST_DB_SERVER_PID}" 2>/dev/null; then
    kill "${TEST_DB_SERVER_PID}" 2>/dev/null || true
    wait "${TEST_DB_SERVER_PID}" 2>/dev/null || true
  fi

  rm -rf "${TEST_TMPDIR}"
}

wait_for_mysql() {
  local attempts=0
  while ! mysql --protocol=SOCKET --socket="${TEST_DB_SOCKET}" -u root -e 'SELECT 1' >/dev/null 2>&1; do
    attempts=$((attempts + 1))
    if [[ "${attempts}" -ge 40 ]]; then
      echo "MariaDB failed to start"
      cat "${TEST_DB_LOG}" || true
      return 1
    fi
    sleep 0.25
  done
}

wait_for_php_server() {
  local attempts=0
  while ! curl -fsS "${TEST_BASE_URL}/index.php" >/dev/null 2>&1; do
    attempts=$((attempts + 1))
    if [[ "${attempts}" -ge 40 ]]; then
      echo "PHP server failed to start"
      cat "${TEST_APP_LOG}" || true
      return 1
    fi
    sleep 0.25
  done
}

start_test_stack() {
  trap cleanup_test_stack EXIT

  mkdir -p "${TEST_DB_DIR}"
  mariadb-install-db --datadir="${TEST_DB_DIR}" --auth-root-authentication-method=normal >/dev/null 2>&1

  mysqld \
    --no-defaults \
    --datadir="${TEST_DB_DIR}" \
    --socket="${TEST_DB_SOCKET}" \
    --pid-file="${TEST_DB_PID}" \
    --bind-address=127.0.0.1 \
    --port="${TEST_DB_PORT}" \
    >"${TEST_DB_LOG}" 2>&1 &
  TEST_DB_SERVER_PID=$!

  wait_for_mysql

  mysql --protocol=SOCKET --socket="${TEST_DB_SOCKET}" -u root < "${PROJECT_ROOT}/database/lenscraft.sql"
  mysql --protocol=SOCKET --socket="${TEST_DB_SOCKET}" -u root < "${PROJECT_ROOT}/database/seed-lenscraft.sql"

  DB_HOST=127.0.0.1 \
  DB_PORT="${TEST_DB_PORT}" \
  DB_NAME=lenscraft \
  DB_USER=root \
  DB_PASS='' \
  php -S "127.0.0.1:${TEST_APP_PORT}" -t "${PROJECT_ROOT}" \
    >"${TEST_APP_LOG}" 2>&1 &
  TEST_APP_PID=$!

  wait_for_php_server
}

mysql_test() {
  mysql --protocol=SOCKET --socket="${TEST_DB_SOCKET}" -u root "$@"
}
