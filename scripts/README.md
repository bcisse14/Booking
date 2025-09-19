E2E smoke test script

This folder contains a small curl/jq-based script to run a basic end-to-end smoke test against the Prise de RDV backend.

Usage:

1. Ensure you have jq installed (used to pretty-print JSON): sudo apt-get install -y jq
2. Run the script pointing at your backend (defaults to the deployed Fly backend):

BACKEND_URL="https://booking-backend-cold-water-8579.fly.dev" ./scripts/e2e.sh

The script will:
- list available slots
- create a slot
- create an appointment for that slot
- try to cancel the appointment (if a cancel token is visible or the debug endpoint exists)
- print the final slot state

Note: the script creates resources on the live backend; clean-up is manual.
