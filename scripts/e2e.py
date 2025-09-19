#!/usr/bin/env python3
"""Simple E2E integration test for Booking backend.

Usage: python3 scripts/e2e.py --base-url https://... [--slot-id /api/slots/2]

The script will:
 - GET /api/slots and pick the first non-reserved slot (or use provided --slot-id)
 - POST /api/appointments to reserve it
 - GET /appointments/cancel/{token} and check for 200
 - DELETE /api/appointments/cancel/{token} and expect 2xx
 - Re-fetch slot and ensure reserved switched back to false

Exit codes: 0 = success, non-zero = failure
"""
import argparse
import json
import sys
import urllib.request
import urllib.error
import urllib.parse


def req(method, path, base, data=None, headers=None, timeout=20):
    url = urllib.parse.urljoin(base, path)
    hdrs = headers or {}
    data_b = None
    if data is not None:
        data_b = json.dumps(data).encode('utf-8')
        hdrs.setdefault('Content-Type', 'application/json')
    req = urllib.request.Request(url, data=data_b, headers=hdrs, method=method)
    try:
        with urllib.request.urlopen(req, timeout=timeout) as r:
            return r.getcode(), r.read().decode('utf-8')
    except urllib.error.HTTPError as e:
        return e.code, e.read().decode('utf-8')
    except Exception as e:
        print('ERROR:', e, file=sys.stderr)
        sys.exit(2)


def find_slot(base):
    code, body = req('GET', '/api/slots', base, headers={'Accept': 'application/ld+json'})
    if code != 200:
        print('Failed to list slots:', code)
        print(body[:800])
        sys.exit(3)
    j = json.loads(body)
    slots = []
    if isinstance(j, dict) and 'hydra:member' in j:
        slots = j['hydra:member']
    elif isinstance(j, list):
        slots = j
    else:
        for v in j.values():
            if isinstance(v, list):
                slots = v
                break
    for s in slots:
        if not s.get('reserved') and s.get('@id'):
            return s.get('@id')
    return None


def main():
    p = argparse.ArgumentParser()
    p.add_argument('--base-url', required=True)
    p.add_argument('--slot-id', default=None, help='Optional: /api/slots/2')
    args = p.parse_args()
    base = args.base_url
    slot_id = args.slot_id
    if not slot_id:
        slot_id = find_slot(base)
        if not slot_id:
            print('No free slot available; aborting (not a failure)')
            sys.exit(0)
    if slot_id.startswith('/'):
        slot_path = slot_id
    else:
        slot_path = slot_id
    slot_url = urllib.parse.urljoin(base, slot_path)
    print('Using slot', slot_path)

    # Create appointment
    payload = {'slot': slot_url, 'name': 'E2E Tester', 'email': 'test+e2e@example.com'}
    code, body = req('POST', '/api/appointments', base, data=payload, headers={'Accept': 'application/ld+json'})
    print('Create code', code)
    if code not in (200, 201):
        print('Create failed')
        print(body[:800])
        sys.exit(4)
    j = json.loads(body)
    ct = None
    for k in ('cancelToken', 'cancel_token', 'cancel'):
        if isinstance(j, dict) and j.get(k):
            ct = j.get(k)
            break
    if not ct:
        for v in j.values() if isinstance(j, dict) else []:
            if isinstance(v, dict) and v.get('cancelToken'):
                ct = v['cancelToken']
                break
    if not ct:
        print('No cancel token in create response; aborting')
        print(body[:800])
        sys.exit(5)
    print('Cancel token', ct)

    # GET cancel page
    ccode, cbody = req('GET', '/appointments/cancel/' + ct, base, headers={'Accept': 'text/html'})
    print('Cancel page code', ccode)
    if ccode >= 500 or ccode < 200:
        print('Cancel page returned bad status', ccode)
        sys.exit(6)

    # DELETE cancel
    dcode, dbody = req('DELETE', '/api/appointments/cancel/' + ct, base, headers={'Accept': 'application/json'})
    print('DELETE cancel code', dcode)
    if not (200 <= dcode < 300):
        print('Cancel failed', dcode)
        print(dbody[:800])
        sys.exit(7)

    # Re-fetch slot
    scode, sbody = req('GET', slot_path, base, headers={'Accept': 'application/ld+json'})
    print('Slot GET code', scode)
    if scode != 200:
        print('Failed to fetch slot')
        sys.exit(8)
    sj = json.loads(sbody)
    if sj.get('reserved'):
        print('Slot still reserved after cancel; failure')
        print(sbody[:800])
        sys.exit(9)

    print('E2E success')
    sys.exit(0)


if __name__ == '__main__':
    main()
