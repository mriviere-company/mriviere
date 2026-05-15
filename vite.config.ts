import { defineConfig } from 'vite';
import vue from '@vitejs/plugin-vue';
import symfonyPlugin from 'vite-plugin-symfony';
import { fileURLToPath, URL } from 'node:url';

export default defineConfig({
  plugins: [
    vue(),
    symfonyPlugin(),
  ],
  resolve: {
    alias: {
      '@': fileURLToPath(new URL('./assets', import.meta.url)),
    },
  },
  build: {
    outDir: 'public/build',
    emptyOutDir: true,
    manifest: true,
    // Top-level await (used in assets/i18n/index.ts to lazy-load the active locale)
    // requires the 2022 baseline. All evergreen browsers support it since 2022/2023.
    target: 'es2022',
    rollupOptions: {
      input: {
        app: './assets/main.ts',
      },
      output: {
        manualChunks: {
          'vue-runtime': ['vue', 'vue-router', 'pinia'],
          'vue-i18n': ['vue-i18n'],
          'motion': ['@vueuse/motion', '@vueuse/core'],
          'icons': ['lucide-vue-next'],
        },
      },
    },
    sourcemap: false,
  },
  css: {
    preprocessorOptions: {
      scss: {
        api: 'modern-compiler',
      },
    },
  },
  server: {
    port: 5173,
    strictPort: true,
    origin: 'http://localhost:5173',
  },
  test: {
    globals: true,
    environment: 'happy-dom',
    setupFiles: ['./assets/__tests__/setup.ts'],
    include: ['assets/**/*.test.ts'],
    exclude: ['node_modules', 'tests/e2e/**', 'public/**'],
  },
});
