import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
    ],
    build: {
        rollupOptions: {
            external: [
                '/fonts/MYRIADPRO-REGULAR.woff',
                '/fonts/MYRIADPRO-BOLD.woff',
                '/fonts/MYRIADPRO-SEMIBOLD.woff',
                '/fonts/MyriadPro-Light.woff',
                '/fonts/MYRIADPRO-COND.woff',
                '/fonts/MYRIADPRO-BOLDCOND.woff'
            ]
        }
    }
});
