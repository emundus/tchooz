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
            '@': fileURLToPath(new URL('./vue/src', import.meta.url))
        },
    },
    build: {
        lib: {
            // the entry file that is loaded whenever someone imports
            // your plugin in their app
            entry: fileURLToPath(new URL('./vue/src/main.js', import.meta.url)),

            // the exposed global variable
            // is required when formats includes 'umd' or 'iife'
            name: 'Dashboard',

            // the proper extensions will be added, ie:
            // name.js (es module)
            // name.umd.cjs) (common js module)
            // default fileName is the name option of package.json
            fileName: 'dashboard'
        },
        minify: false,
        outDir: path.resolve(__dirname, '../../media/mod_emundus_dashboard_vue'),
        emptyOutDir: true,
        rollupOptions: {
            output: {
                entryFileNames: 'app.js',
                chunkFileNames: 'app.js.map',
                assetFileNames: (assetInfo) => {
                    if (assetInfo.name.endsWith('.css')) {
                        return 'app.css';
                    }
                    return  'assets/'  + assetInfo.name;
                }
            },
        },
        cssCodeSplit: false
    },
    define: {
        'process.env': {}
    }
});
