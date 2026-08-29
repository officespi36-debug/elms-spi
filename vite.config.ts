import { defineConfig } from 'vite'
import laravel from 'laravel-vite-plugin'
import vue from '@vitejs/plugin-vue'
import tailwindcss from '@tailwindcss/vite'
import ui from '@nuxt/ui/vite'
import { VitePWA } from 'vite-plugin-pwa'
import path from 'path'

export default defineConfig({
  plugins: [
    laravel({
      input: ['resources/css/app.css', 'resources/js/app.ts'],
      ssr: 'resources/js/ssr.ts',
      refresh: true,
    }),
    vue(),
    tailwindcss(),
    ui({ ui: { colors: { primary: 'blue', neutral: 'slate' } } }),
    VitePWA({
      registerType: 'autoUpdate',
      strategies: 'injectManifest',
      srcDir: 'resources/js',
      filename: 'sw.ts',
      manifest: {
        name: 'E.LMS — Learn Anywhere',
        short_name: 'E.LMS',
        theme_color: '#1e40af',
        display: 'standalone',
        start_url: '/dashboard',
        icons: [
          { src: '/pwa-192.png', sizes: '192x192', type: 'image/png' },
          { src: '/pwa-512.png', sizes: '512x512', type: 'image/png', purpose: 'any maskable' },
        ],
      },
      injectManifest: { globPatterns: ['**/*.{js,css,html,svg,png,woff2}'] },
      devOptions: { enabled: true, type: 'module' },
    }),
  ],
  resolve: { alias: { '@': path.resolve(__dirname, 'resources/js') } },
  build: {
    target: 'es2020',
    cssTarget: 'safari14',
    minify: 'esbuild',
    cssMinify: true,
    reportCompressedSize: false,
    rollupOptions: {
      output: {
        manualChunks(id) {
          if (id.includes('node_modules')) {
            if (id.includes('vue') || id.includes('@inertiajs') || id.includes('pinia')) {
              return 'vendor-vue'
            }
            if (id.includes('@nuxt/ui') || id.includes('primevue') || id.includes('@primevue') || id.includes('lucide-vue-next') || id.includes('primeicons')) {
              return 'vendor-ui'
            }
            if (id.includes('@codemirror') || id.includes('codemirror') || id.includes('vue-codemirror')) {
              return 'vendor-editor'
            }
            if (id.includes('apexcharts') || id.includes('vue3-apexcharts')) {
              return 'vendor-charts'
            }
            if (id.includes('pdfjs-dist') || id.includes('plyr') || id.includes('hls.js')) {
              return 'vendor-media'
            }
            if (id.includes('@tanstack') || id.includes('@vueuse')) {
              return 'vendor-utils'
            }
            return 'vendor'
          }
        },
      },
    },
    chunkSizeWarningLimit: 1200,
  },
})
