#!/usr/bin/env bash
# Drop all tables and re-run migrations (XAMPP PHP). Destroys all data.
# Usage: ./migrate-fresh-local.sh

set -e
cd "$(dirname "$0")"

PHP_BIN="${PHP:-php}"
if ! command -v "$PHP_BIN" >/dev/null 2>&1; then
  if [ -x "/Applications/XAMPP/xamppfiles/bin/php" ]; then
    PHP_BIN="/Applications/XAMPP/xamppfiles/bin/php"
  else
    echo "PHP binary not found. Install PHP or set PHP= path."
    exit 1
  fi
fi

echo "Using PHP: $($PHP_BIN -v 2>/dev/null | head -n 1)"
echo "Running migrate:fresh (drops all tables, re-migrates)..."
$PHP_BIN artisan migrate:fresh --force
echo "Done."
