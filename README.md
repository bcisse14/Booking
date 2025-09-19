# Prise de RDV
## End-to-end (E2E) testing

We've added a lightweight E2E script to exercise the booking lifecycle (list → create → cancel).

Run locally:

```bash
# from repo root
python3 scripts/e2e.py --base-url https://booking-backend-cold-water-8579.fly.dev
```

CI smoke-test:

- The deploy workflow now runs a quick smoke-test after deploy which GETs an invalid cancel token and fails the job if the cancel page returns 5xx or is unreachable.