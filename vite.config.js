import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import { VitePWA } from 'vite-plugin-pwa';

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
        VitePWA({
            registerType: 'autoUpdate',
            includeAssets: ['images/logo.png', 'images/ico.png', 'images/logo_white.png'],
            manifest: {
                name: 'MyTicketO - Billetterie en ligne',
                short_name: 'MyTicketO',
                description: 'Plateforme de billetterie en ligne pour événements au Gabon',
                theme_color: '#272d63',
                background_color: '#ffffff',
                display: 'standalone',
                orientation: 'portrait',
                scope: '/',
                start_url: '/',
                icons: [
                    {
                        src: '/images/ico.png',
                        sizes: '192x192',
                        type: 'image/png',
                        purpose: 'any maskable'
                    },
                    {
                        src: '/images/logo.png',
                        sizes: '512x512',
                        type: 'image/png',
                        purpose: 'any maskable'
                    }
                ],
                categories: ['entertainment', 'lifestyle', 'shopping'],
                lang: 'fr-FR',
                dir: 'ltr'
            },
            workbox: {
                globPatterns: ['**/*.{js,css,html,ico,png,svg,woff,woff2}'],
                runtimeCaching: [
                    {
                        urlPattern: /^https:\/\/fonts\.googleapis\.com\/.*/i,
                        handler: 'CacheFirst',
                        options: {
                            cacheName: 'google-fonts-cache',
                            expiration: {
                                maxEntries: 10,
                                maxAgeSeconds: 60 * 60 * 24 * 365 // 1 year
                            },
                            cacheableResponse: {
                                statuses: [0, 200]
                            }
                        }
                    },
                    {
                        urlPattern: /^https:\/\/fonts\.bunny\.net\/.*/i,
                        handler: 'CacheFirst',
                        options: {
                            cacheName: 'bunny-fonts-cache',
                            expiration: {
                                maxEntries: 10,
                                maxAgeSeconds: 60 * 60 * 24 * 365 // 1 year
                            },
                            cacheableResponse: {
                                statuses: [0, 200]
                            }
                        }
                    },
                    {
                        urlPattern: /\/api\/v1\/events/,
                        handler: 'NetworkFirst',
                        options: {
                            cacheName: 'api-events-cache',
                            expiration: {
                                maxEntries: 50,
                                maxAgeSeconds: 60 * 5 // 5 minutes
                            },
                            networkTimeoutSeconds: 10
                        }
                    },
                    {
                        urlPattern: /\/storage\/images\/.*/i,
                        handler: 'CacheFirst',
                        options: {
                            cacheName: 'images-cache',
                            expiration: {
                                maxEntries: 100,
                                maxAgeSeconds: 60 * 60 * 24 * 30 // 30 days
                            }
                        }
                    }
                ]
            },
            devOptions: {
                enabled: true
            }
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
