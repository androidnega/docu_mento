#!/usr/bin/env bash
# Safe pull for cPanel / shared hosting: never runs `git checkout -b main` when main already exists.
# Usage (when you have shell or support runs it): from the repository root, run:
#   bash scripts/cpanel-git-safe-pull.sh
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT"

if ! git rev-parse --git-dir >/dev/null 2>&1; then
  echo "Error: not a git repository: $ROOT" >&2
  exit 1
fi

git fetch origin

if git show-ref --verify --quiet refs/heads/main; then
  git checkout main
  git pull --ff-only origin main || git pull origin main
elif git show-ref --verify --quiet refs/remotes/origin/main; then
  git checkout -b main origin/main
else
  echo "Error: no local or remote branch 'main' found." >&2
  exit 1
fi

echo "OK: on branch $(git branch --show-current), last commit: $(git log -1 --oneline)"
