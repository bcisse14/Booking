export const API_ORIGIN = import.meta.env.VITE_API_URL || 'http://localhost:8000';
export const API = (path) => {
  const origin = API_ORIGIN.replace(/\/$/, '');
  const p = path.startsWith('/') ? path : '/' + path;
  return `${origin}${p}`;
};
