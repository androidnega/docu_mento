#!/usr/bin/env bash
# Run Laravel key:generate and all migrations using local/XAMPP PHP.
# Usage: ./migrate-local.sh

set -e
cd "$(dirname "$0")"

PHP_BIN="${PHP:-php}"

# If PHP is not in PATH, fall back to XAMPP's PHP.
if ! command -v "$PHP_BIN" >/dev/null 2>&1; then
  if [ -x "/Applications/XAMPP/xamppfiles/bin/php" ]; then
    PHP_BIN="/Applications/XAMPP/xamppfiles/bin/php"
  else
    echo "PHP binary not found."
    echo "Install PHP or set the PHP environment variable to the PHP executable path."
    exit 1
  fi
fi

echo "Using PHP: $($PHP_BIN -v 2>/dev/null | head -n 1)"

echo "Generating APP_KEY (if missing)..."
$PHP_BIN artisan key:generate || true

echo "Running migrations..."
$PHP_BIN artisan migrate --force

echo "Done."

