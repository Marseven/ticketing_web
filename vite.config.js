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
            includeAssets: ['images/logo.png', 'images/ico.png', 'images/icon-192.png', 'images/icon-512.png', 'images/logo_white.png'],
            manifest: {
                name: 'Primea - Billetterie en ligne',
                short_name: 'Primea',
                description: 'Plateforme de billetterie en ligne pour événements au Gabon',
                theme_color: '#272d63',
                background_color: '#ffffff',
                display: 'standalone',
                orientation: 'portrait',
                scope: '/',
                start_url: '/',
                icons: [
                    {
                        src: '/images/icon-192.png',
                        sizes: '192x192',
                        type: 'image/png',
                        purpose: 'any'
                    },
                    {
                        src: '/images/icon-512.png',
                        sizes: '512x512',
                        type: 'image/png',
                        purpose: 'any'
                    },
                    {
                        src: '/images/icon-512.png',
                        sizes: '512x512',
                        type: 'image/png',
                        purpose: 'maskable'
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
                // Découpage : uniquement les grosses libs partagées, par
                // bibliothèque. Surtout PAS de chunk par dossier de pages
                // (admin/, account/…) : Rollup rattachait ces chunks à
                // l'entrée en import STATIQUE, si bien qu'un visiteur de
                // l'accueil téléchargeait tout l'admin. Les pages sont déjà
                // découpées route par route par les imports dynamiques.
                manualChunks(id) {
                    if (!id.includes('node_modules')) return;
                    if (id.includes('/leaflet')) return 'leaflet';
                    if (id.includes('/chart.js') || id.includes('/vue-chartjs')) return 'charts';
                    if (id.includes('/html2canvas')) return 'html2canvas';
                    if (id.includes('/sweetalert2')) return 'sweetalert2';
                    if (id.includes('/vue/') || id.includes('/@vue/') || id.includes('/vue-router/') || id.includes('/pinia/')) {
                        return 'vue-vendor';
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
