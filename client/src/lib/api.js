// Prefer an explicit VITE_API_URL set in environment. If absent, use a relative path
// so the frontend will call the same origin that served the app. This avoids
// attempting to call http://localhost:8000 in production (which ad-blockers or
// the browser may block) when the backend URL isn't configured.
export const API_ORIGIN = import.meta.env.VITE_API_URL ?? '';
export const API = (path) => {
  // start from the build-time origin (may be empty)
  let origin = (API_ORIGIN || '').replace(/\/$/, '');

  // Safety: if the build-time origin points to localhost but the app is
  // currently served from a non-localhost host (production), prefer a
  // relative URL so the frontend talks to the same origin that served it.
  try {
    if (origin && /localhost|127\.0\.0\.1/.test(origin)) {
      const host = (typeof window !== 'undefined' && window.location && window.location.hostname) ? window.location.hostname : '';
      if (host && host !== 'localhost' && host !== '127.0.0.1') {
        origin = '';
      }
    }
  } catch {
    // ignore and fall back to build-time origin
  }

  // Runtime guard: if no build-time origin is provided and the app is running
  // on Vercel (or other known static host), prefer the deployed backend host.
  // This prevents the frontend from POSTing to the Vercel origin (which may
  // respond 405) when VITE_API_URL wasn't set at build time.
  try {
    if (!origin && typeof window !== 'undefined' && window.location && window.location.hostname) {
      const host = window.location.hostname;
      // detect common Vercel host pattern and fallback to the Fly backend
      if (host.endsWith('.vercel.app') || host.endsWith('.vercel.sh')) {
        origin = 'https://booking-backend-cold-water-8579.fly.dev';
      }
    }
  } catch {
    // ignore
  }

  const p = path.startsWith('/') ? path : '/' + path;
  return origin ? `${origin}${p}` : `${p}`;
};
