#!/usr/bin/env bash
#
# backup-db.sh — mysqldump helper that enforces the AGENTS.md §8 / §13 naming
# convention. Bash-only (POSIX sh + mysqldump). Works in Git Bash / Laragon
# Cmder / WSL / macOS / Linux CI. Windows: run under Git Bash.
#
# Usage:
#   scripts/backup-db.sh <task-id> [--purpose="short description"]
#
# Example:
#   scripts/backup-db.sh b6.1-product-children --purpose="pre-ALTER product-child translations"
#
# Output:
#   storage/app/db-backups/pre-<task-id>-YYYYMMDD-HHMMSS.sql
#
# Reads DB creds from .env via the artisan tinker fallback OR directly if
# DB_* vars are exported. Bails out (non-zero exit) on any failure so
# calling code / hooks can rely on the result.

set -euo pipefail

TASK_ID="${1:-}"
PURPOSE=""

if [[ -z "$TASK_ID" ]]; then
    echo "ERROR: task-id required." >&2
    echo "Usage: scripts/backup-db.sh <task-id> [--purpose=\"...\"]" >&2
    exit 2
fi
shift || true

while [[ $# -gt 0 ]]; do
    case "$1" in
        --purpose=*) PURPOSE="${1#*=}"; shift ;;
        *) echo "Unknown arg: $1" >&2; exit 2 ;;
    esac
done

# Resolve project root (this script lives in scripts/).
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "$SCRIPT_DIR/.." && pwd)"
cd "$PROJECT_ROOT"

# Read DB config via artisan (single source of truth: .env → config).
CONFIG_JSON="$(php artisan tinker --execute="echo json_encode([
    'host' => config('database.connections.mysql.host'),
    'port' => config('database.connections.mysql.port'),
    'user' => config('database.connections.mysql.username'),
    'pass' => config('database.connections.mysql.password'),
    'db'   => config('database.connections.mysql.database'),
]);")"

DB_HOST="$(echo "$CONFIG_JSON" | php -r 'echo json_decode(fgets(STDIN), true)["host"];')"
DB_PORT="$(echo "$CONFIG_JSON" | php -r 'echo json_decode(fgets(STDIN), true)["port"];')"
DB_USER="$(echo "$CONFIG_JSON" | php -r 'echo json_decode(fgets(STDIN), true)["user"];')"
DB_PASS="$(echo "$CONFIG_JSON" | php -r 'echo json_decode(fgets(STDIN), true)["pass"];')"
DB_NAME="$(echo "$CONFIG_JSON" | php -r 'echo json_decode(fgets(STDIN), true)["db"];')"

if [[ -z "$DB_NAME" ]]; then
    echo "ERROR: could not resolve DB name from Laravel config." >&2
    exit 3
fi

TS="$(date +%Y%m%d-%H%M%S)"
OUT_DIR="storage/app/db-backups"
mkdir -p "$OUT_DIR"
OUT="$OUT_DIR/pre-${TASK_ID}-${TS}.sql"

# Locate mysqldump: PATH first, then Laragon default.
MYSQLDUMP="$(command -v mysqldump || true)"
if [[ -z "$MYSQLDUMP" ]]; then
    for candidate in \
        "/c/laragon/bin/mysql/mysql-8.4.3-winx64/bin/mysqldump" \
        "/c/laragon/bin/mysql/mysql-8.0.30-winx64/bin/mysqldump"; do
        if [[ -x "$candidate" ]]; then MYSQLDUMP="$candidate"; break; fi
    done
fi
if [[ -z "$MYSQLDUMP" ]]; then
    echo "ERROR: mysqldump not found on PATH or in Laragon default locations." >&2
    exit 4
fi

# Build args. Only pass -p when a password exists — an empty --password flag
# breaks the CLI.
ARGS=(-h"$DB_HOST" -P"$DB_PORT" -u"$DB_USER"
      --single-transaction --routines --triggers
      --skip-lock-tables
      --databases "$DB_NAME")
if [[ -n "$DB_PASS" ]]; then
    export MYSQL_PWD="$DB_PASS"
fi

echo "→ Backing up $DB_NAME to $OUT ..."
if ! "$MYSQLDUMP" "${ARGS[@]}" --result-file="$OUT"; then
    echo "ERROR: mysqldump failed. Backup file may be partial:" >&2
    echo "       $OUT" >&2
    exit 5
fi

# Sanity: dump file must contain the expected header AND be > 1 KB.
if ! head -1 "$OUT" | grep -q "MySQL dump"; then
    echo "ERROR: dump file has no MySQL header — refusing to trust it." >&2
    rm -f "$OUT"
    exit 6
fi
SIZE_BYTES="$(wc -c < "$OUT")"
if [[ "$SIZE_BYTES" -lt 1024 ]]; then
    echo "ERROR: dump file suspiciously small ($SIZE_BYTES bytes)." >&2
    rm -f "$OUT"
    exit 7
fi

# Optionally record purpose in a companion .meta file (no shell escaping games).
if [[ -n "$PURPOSE" ]]; then
    printf 'task_id: %s\ncreated_at: %s\npurpose: %s\ndb: %s\nsize_bytes: %s\n' \
        "$TASK_ID" "$TS" "$PURPOSE" "$DB_NAME" "$SIZE_BYTES" > "$OUT.meta"
fi

# Prune: any pre-*.sql older than 90 days is stale (per AGENTS.md §13 retention).
# We only warn — deletion is manual so nothing is lost silently.
STALE_COUNT="$(find "$OUT_DIR" -maxdepth 1 -type f -name 'pre-*.sql' -mtime +90 2>/dev/null | wc -l | tr -d '[:space:]')"
if [[ "$STALE_COUNT" -gt 0 ]]; then
    echo "NOTE: $STALE_COUNT backup(s) older than 90 days in $OUT_DIR (review + archive)."
fi

echo "✓ Backup OK: $OUT ($SIZE_BYTES bytes)"
