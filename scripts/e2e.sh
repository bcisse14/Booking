#!/usr/bin/env bash
set -euo pipefail

# Simple E2E smoke test for the Booking API.
# Usage: BACKEND_URL="https://..." ./scripts/e2e.sh

BACKEND_URL="${BACKEND_URL:-https://booking-backend-cold-water-8579.fly.dev}"

echo "Using BACKEND_URL=$BACKEND_URL"

echo "1) List available slots (reserved=false)"
curl -sS "$BACKEND_URL/api/slots?reserved=false" -H "Accept: application/json" | jq . || true

echo
echo "2) Create a new slot"
SLOT_PAYLOAD='{"datetime":"2025-09-30T08:00:00Z"}'
SLOT_LOCATION=$(curl -sS -D - -o /dev/null -X POST "$BACKEND_URL/api/slots" -H "Content-Type: application/json" -d "$SLOT_PAYLOAD" | grep -i '^location:' | awk '{print $2}' | tr -d '\r' || true)
if [ -z "$SLOT_LOCATION" ]; then
  echo "Failed to create slot or Location header missing. Response below:" >&2
  curl -sS -X POST "$BACKEND_URL/api/slots" -H "Content-Type: application/json" -d "$SLOT_PAYLOAD" || true
  exit 1
fi
echo "Created slot at: $SLOT_LOCATION"

SLOT_ID=$(basename "$SLOT_LOCATION")

echo
echo "3) Create an appointment for the slot"
APPT_PAYLOAD=$(jq -n --arg slot "/api/slots/$SLOT_ID" --arg name "Test User" --arg email "test.user@example.com" '{"slot":$slot,"name":$name,"email":$email}')
APPT_LOCATION=$(curl -sS -D - -o /dev/null -X POST "$BACKEND_URL/api/appointments" -H "Content-Type: application/json" -d "$APPT_PAYLOAD" | grep -i '^location:' | awk '{print $2}' | tr -d '\r' || true)
if [ -z "$APPT_LOCATION" ]; then
  echo "Failed to create appointment or Location header missing. Response below:" >&2
  curl -sS -X POST "$BACKEND_URL/api/appointments" -H "Content-Type: application/json" -d "$APPT_PAYLOAD" || true
  exit 1
fi
echo "Created appointment at: $APPT_LOCATION"
APPT_ID=$(basename "$APPT_LOCATION")

echo
echo "4) Attempt cancellation: try DELETE /api/appointments/cancel/{token} — look for token via appointment entity"
# The API typically hides the cancel token; try GET /api/appointments/{id}. If the API returns NotExposed, try the debug endpoint if present.
APPT_JSON=$(curl -sS "$BACKEND_URL/api/appointments/$APPT_ID" -H "Accept: application/ld+json" || true)
echo "Appointment entity response:"; echo "$APPT_JSON" | jq . || true

# Try to extract cancel_token field (possible names: cancelToken, cancel_token)
CANCEL_TOKEN=$(echo "$APPT_JSON" | jq -r '.cancelToken // .cancel_token // empty' || true)
if [ -n "$CANCEL_TOKEN" ]; then
  echo "Found cancel token in appointment entity: $CANCEL_TOKEN"
  echo "Calling DELETE $BACKEND_URL/api/appointments/cancel/$CANCEL_TOKEN"
  curl -sS -X DELETE "$BACKEND_URL/api/appointments/cancel/$CANCEL_TOKEN" -D - -o /dev/null || true
else
  echo "No cancel token visible on appointment entity. Trying debug endpoint /_debug/appointment-token/$APPT_ID (may not exist)."
  DEBUG_JSON=$(curl -sS "$BACKEND_URL/_debug/appointment-token/$APPT_ID" -H "Accept: application/json" || true)
  echo "$DEBUG_JSON" | jq . || true
  CANCEL_TOKEN=$(echo "$DEBUG_JSON" | jq -r '.cancelToken // .cancel_token // empty' || true)
  if [ -n "$CANCEL_TOKEN" ]; then
    echo "Found cancel token via debug endpoint: $CANCEL_TOKEN"
    echo "Calling DELETE $BACKEND_URL/api/appointments/cancel/$CANCEL_TOKEN"
    curl -sS -X DELETE "$BACKEND_URL/api/appointments/cancel/$CANCEL_TOKEN" -D - -o /dev/null || true
  else
    echo "No cancel token available. Manual cancellation step cannot proceed."
  fi
fi

echo
echo "5) Verify slot reservation state"
curl -sS "$BACKEND_URL/api/slots/$SLOT_ID" -H "Accept: application/ld+json" | jq . || true

echo "E2E script finished. Clean up created resources manually if needed."
