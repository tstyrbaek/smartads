import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'

export default defineConfig({
  plugins: [vue()],
  base: '/',
  build: {
    outDir: '../backend/public',
    emptyOutDir: false,
  },
  server: {
    port: 5174,
    strictPort: true,
    proxy: {
      '/api': {
        target: 'https://smartadd.ddev.site:33005',
        changeOrigin: true,
        secure: false,
      },
      '/sanctum': {
        target: 'https://smartadd.ddev.site:33005',
        changeOrigin: true,
        secure: false,
      },
      '/storage': {
        target: 'https://smartadd.ddev.site:33005',
        changeOrigin: true,
        secure: false,
      },
    },
  },
})
