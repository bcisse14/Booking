#!/usr/bin/env bash
set -euo pipefail

# Convenience wrapper to start local dev mock + Vite from project root
cd "$(dirname "$0")/client"
if [ ! -f ./start-dev-local.sh ]; then
  echo "Client helper start-dev-local.sh missing in client/." >&2
  exit 1
fi

echo "Running client/start-dev-local.sh from project root (cwd=$(pwd))"
bash ./start-dev-local.sh
