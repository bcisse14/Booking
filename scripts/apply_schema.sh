#!/usr/bin/env bash
set -euo pipefail

# Apply server/sql/init_schema.sql to the database pointed by DATABASE_URL
# Usage:
#   DATABASE_URL="postgres://user:pass@host:5432/dbname" ./scripts/apply_schema.sh

SQL_FILE="server/sql/init_schema.sql"

if [ ! -f "$SQL_FILE" ]; then
  echo "SQL file not found: $SQL_FILE" >&2
  exit 1
fi

if [ -z "${DATABASE_URL:-}" ]; then
  echo "DATABASE_URL is not set. To apply the schema, run:" >&2
  echo "DATABASE_URL=\"postgres://user:pass@host:5432/dbname\" $0" >&2
  exit 2
fi

echo "Applying schema from $SQL_FILE to database in DATABASE_URL..."

# Use psql. If psql is not available, print instructions without attempting to run.
if command -v psql >/dev/null 2>&1; then
  # psql understands DATABASE_URL directly
  PGPASSWORD_ARG=""
  # Run the SQL file with psql
  echo "Running psql -f $SQL_FILE"
  psql "$DATABASE_URL" -f "$SQL_FILE"
  echo "Schema applied successfully."
else
  echo "psql not found on this machine. You can run the following command on a machine with psql:" >&2
  echo
  echo "psql \"$DATABASE_URL\" -f $SQL_FILE" >&2
  echo
  echo "Or use Supabase SQL editor to paste the contents of $SQL_FILE and run it." >&2
fi
