#!/usr/bin/env bash
set -euo pipefail

echo "Starting dev mock API on port 8000..."
node ./dev-mock.js &
MOCK_PID=$!
echo "Mock PID: $MOCK_PID"

echo "Installing deps if needed..."
npm install

echo "Starting Vite dev server on port 5173 (strict) ..."
# Force Vite to start on 5173 to avoid stale multiple instances confusing the browser
npm run dev -- --port 5173 --strictPort

# On exit, kill the mock
trap 'echo KILLING MOCK; kill $MOCK_PID' EXIT
