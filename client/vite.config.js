import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';

export default defineConfig({
  plugins: [react()],
  server: {
    proxy: {
  '/api': 'http://localhost:8000',  // backend Symfony (dev proxy) - in production set VITE_API_URL
    },
  },
});