import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';

export default defineConfig({
  plugins: [react()],
  server: {
    proxy: {
  '/api': 'http://localhost:8010',  // dev mock on 8010; change to 8000 if running Symfony locally
    },
  },
});