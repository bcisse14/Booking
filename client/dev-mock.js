// Simple in-memory mock API for local development
const http = require('http');
const url = require('url');

let slots = [
  { id: 1, datetime: '2025-09-30T08:00:00+00:00', reserved: false }
];
let appointments = [];

function sendJson(res, status, obj) {
  const s = JSON.stringify(obj);
  res.writeHead(status, {
    'Content-Type': 'application/ld+json; charset=utf-8',
    'Content-Length': Buffer.byteLength(s),
  });
  res.end(s);
}

const server = http.createServer((req, res) => {
  const parsed = url.parse(req.url, true);
  const method = req.method.toUpperCase();
  // Simple routing
  if (parsed.pathname === '/api/slots' && method === 'GET') {
    const reserved = parsed.query.reserved;
    if (reserved === 'false') {
      sendJson(res, 200, slots.filter(s => s.reserved === false));
    } else {
      sendJson(res, 200, slots);
    }
    return;
  }

  if (parsed.pathname === '/api/slots' && method === 'POST') {
    let body = '';
    req.on('data', chunk => body += chunk);
    req.on('end', () => {
      const id = slots.length + 1;
      const obj = { id, datetime: JSON.parse(body).datetime, reserved: false };
      slots.push(obj);
      res.writeHead(201, { Location: `/api/slots/${id}` });
      res.end();
    });
    return;
  }

  // PATCH or PUT for slot updates (e.g., set reserved true)
  if ((parsed.pathname.match(/^\/api\/slots\/\d+$/) || parsed.pathname.match(/^\/api\/slots\/\d+\.json$/)) && (method === 'PATCH' || method === 'PUT')) {
    let body = '';
    req.on('data', chunk => body += chunk);
    req.on('end', () => {
      try {
        const idMatch = parsed.pathname.match(/(\d+)/);
        const id = idMatch ? parseInt(idMatch[0], 10) : null;
        const slot = slots.find(s => s.id === id);
        if (!slot) {
          res.writeHead(404, { 'Content-Type': 'application/problem+json' });
          res.end(JSON.stringify({ status: 404, detail: 'Not found' }));
          return;
        }

        // Attempt to parse JSON body (support application/ld+json or application/json)
        let data = {};
        if (body && body.length > 0) {
          try { data = JSON.parse(body); } catch (e) { data = {}; }
        }

        // Apply allowed updates
        if (data.hasOwnProperty('reserved')) {
          slot.reserved = !!data.reserved;
        }
        // also accept snake_case or other variants
        if (data.hasOwnProperty('isReserved')) {
          slot.reserved = !!data.isReserved;
        }

        // Return updated slot
        res.writeHead(200, { 'Content-Type': 'application/ld+json; charset=utf-8' });
        res.end(JSON.stringify(slot));
      } catch (e) {
        res.writeHead(500, { 'Content-Type': 'application/problem+json' });
        res.end(JSON.stringify({ status: 500, detail: String(e) }));
      }
    });
    return;
  }

  if (parsed.pathname === '/api/appointments' && method === 'POST') {
    let body = '';
    req.on('data', chunk => body += chunk);
    req.on('end', () => {
      const data = JSON.parse(body);
      const id = appointments.length + 1;
      const token = Math.random().toString(36).slice(2, 18);
      const appt = { id, slot: data.slot, name: data.name, email: data.email, cancelToken: token };
      appointments.push(appt);
      // mark slot reserved if exists
      const sid = parseInt(data.slot.split('/').pop(), 10);
      const s = slots.find(x => x.id === sid);
      if (s) s.reserved = true;
      res.writeHead(201, { Location: `/api/appointments/${id}` });
      res.end();
    });
    return;
  }

  // expose cancel endpoint
  if (parsed.pathname.startsWith('/api/appointments/cancel/') && method === 'DELETE') {
    const token = parsed.pathname.split('/').pop();
    const idx = appointments.findIndex(a => a.cancelToken === token);
    if (idx === -1) {
      res.writeHead(404, { 'Content-Type': 'application/problem+json' });
      res.end(JSON.stringify({ status: 404, detail: 'Not found' }));
      return;
    }
    const appt = appointments[idx];
    // free slot
    const sid = parseInt(appt.slot.split('/').pop(), 10);
    const s = slots.find(x => x.id === sid);
    if (s) s.reserved = false;
    appointments.splice(idx, 1);
    res.writeHead(204);
    res.end();
    return;
  }

  // simple appointment item (not exposed by API Platform normally)
  if (parsed.pathname.startsWith('/api/appointments/') && method === 'GET') {
    const id = parseInt(parsed.pathname.split('/').pop(), 10);
    const a = appointments.find(x => x.id === id);
    if (!a) {
      res.writeHead(404, { 'Content-Type': 'application/problem+json' });
      res.end(JSON.stringify({ status: 404, detail: 'Not found' }));
      return;
    }
    sendJson(res, 200, a);
    return;
  }

  // fallback
  res.writeHead(404, { 'Content-Type': 'application/problem+json' });
  res.end(JSON.stringify({ status: 404, detail: 'Not found' }));
});

const PORT = process.env.MOCK_PORT || 8010;
server.listen(PORT, () => console.log(`Dev mock API listening on http://localhost:${PORT}`));
