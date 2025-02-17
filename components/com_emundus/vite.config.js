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
      '@': fileURLToPath(new URL('./src', import.meta.url)),
      '@media': fileURLToPath(new URL('../../media', import.meta.url)),
    },
  },
  build: {
    minify: false,
    outDir: path.resolve(__dirname, '../../media/com_emundus_vue'),
    emptyOutDir: true,
    rollupOptions: {
      output: {
        entryFileNames: 'app_emundus.js',
        chunkFileNames: '[name].js',
        assetFileNames: (assetInfo) => {
          if (assetInfo.name.endsWith('.css')) {
            return 'app_emundus.css';
          }
          return  'assets/'  + assetInfo.name;
        }
      },
      onLog(level, log, handler) {
        if (log.cause && log.cause.message === `Can't resolve original location of error.`) {
          return
        }
        handler(level, log)
      }
    },
    cssCodeSplit: false
  },
  css: {
    preprocessorOptions: {
      scss: {
        api: 'modern-compiler',
        silenceDeprecations: ["legacy-js-api"],
      },
      sass: {
        api: 'modern-compiler',
      }
    }
  },
  base: "/media/com_emundus_vue"
});
