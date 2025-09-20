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
    resolve: {
        alias: {
            vue: 'vue/dist/vue.esm-bundler.js',
        },
    },
    build: {
        // Augmenter la limite de warning pour les chunks
        chunkSizeWarningLimit: 1000,
        
        rollupOptions: {
            external: [
                '/fonts/MYRIADPRO-REGULAR.woff',
                '/fonts/MYRIADPRO-BOLD.woff',
                '/fonts/MYRIADPRO-SEMIBOLD.woff',
                '/fonts/MyriadPro-Light.woff',
                '/fonts/MYRIADPRO-COND.woff',
                '/fonts/MYRIADPRO-BOLDCOND.woff'
            ],
            output: {
                // Créer des chunks manuels pour séparer les vendors
                manualChunks(id) {
                    // Séparer les dépendances node_modules
                    if (id.includes('node_modules')) {
                        // Créer un chunk séparé pour Vue et ses dépendances
                        if (id.includes('vue') || id.includes('@vue')) {
                            return 'vue-vendor';
                        }
                        // Créer un chunk séparé pour pinia
                        if (id.includes('pinia')) {
                            return 'pinia-vendor';
                        }
                        // Créer un chunk séparé pour les autres librairies
                        return 'vendor';
                    }
                    // Séparer les pages admin dans leur propre chunk
                    if (id.includes('/admin/')) {
                        return 'admin';
                    }
                    // Séparer les pages organisateur
                    if (id.includes('/organizer/')) {
                        return 'organizer';
                    }
                    // Séparer les pages account
                    if (id.includes('/account/')) {
                        return 'account';
                    }
                },
                // Optimiser la génération des noms de fichiers
                chunkFileNames: 'assets/js/[name]-[hash].js',
                entryFileNames: 'assets/js/[name]-[hash].js',
                assetFileNames: ({ name }) => {
                    if (/\.(css)$/.test(name ?? '')) {
                        return 'assets/css/[name]-[hash][extname]';
                    }
                    if (/\.(woff|woff2|eot|ttf|otf)$/.test(name ?? '')) {
                        return 'assets/fonts/[name][extname]';
                    }
                    return 'assets/[name]-[hash][extname]';
                }
            }
        }
    }
});
