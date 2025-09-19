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

  const p = path.startsWith('/') ? path : '/' + path;
  return origin ? `${origin}${p}` : `${p}`;
};
