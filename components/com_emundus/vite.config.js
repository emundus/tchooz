import { fileURLToPath, URL } from 'node:url';
import { defineConfig } from 'vite';
import vue from '@vitejs/plugin-vue';
import path from 'path';

// https://vitejs.dev/config/
export default defineConfig({
  plugins: [
    vue(),
  ],
  resolve: {
    //preserveSymlinks: true,
    alias: {
      '@': fileURLToPath(new URL('./src', import.meta.url))
    },
  },
  build: {
    minify: false,
    outDir: path.resolve(__dirname, '../../media/com_emundus_vue'),
    emptyOutDir: true,
    rollupOptions: {
      output: {
        entryFileNames: 'app_emundus.js',
        chunkFileNames: 'app_emundus.js.map',
        assetFileNames: (assetInfo) => {
          if (assetInfo.name.endsWith('.css')) {
            return 'app_emundus.css';
          }
          return  'assets/'  + assetInfo.name;
        }
      },
    },
    cssCodeSplit: false
  },
  base: "/media/com_emundus_vue"
});
