// Prefer an explicit VITE_API_URL set in environment. If absent, use a relative path
// so the frontend will call the same origin that served the app. This avoids
// attempting to call http://localhost:8000 in production (which ad-blockers or
// the browser may block) when the backend URL isn't configured.
export const API_ORIGIN = import.meta.env.VITE_API_URL ?? '';
export const API = (path) => {
  const origin = API_ORIGIN.replace(/\/$/, '');
  const p = path.startsWith('/') ? path : '/' + path;
  // If origin is empty, return a relative path so requests go to the frontend origin.
  return origin ? `${origin}${p}` : `${p}`;
};
