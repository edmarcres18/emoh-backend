import { wayfinder } from '@laravel/vite-plugin-wayfinder';
import tailwindcss from '@tailwindcss/vite';
import vue from '@vitejs/plugin-vue';
import laravel from 'laravel-vite-plugin';
import { defineConfig } from 'vite';
import { resolve } from 'path';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/js/app.ts'],
            ssr: 'resources/js/ssr.ts',
            refresh: true,
        }),
        tailwindcss(),
        wayfinder({
            formVariants: process.env.NODE_ENV !== 'production',
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
            '@': resolve(__dirname, 'resources/js'),
        },
    },
    build: {
        minify: 'terser',
        terserOptions: {
            compress: {
                // Remove console.* statements in production for security
                drop_console: true,
                drop_debugger: true,
            },
        },
        rollupOptions: {
            output: {
                manualChunks: (id) => {
                    // Vendor chunks - split large libraries into separate chunks
                    if (id.includes('node_modules')) {
                        // Vue core libraries
                        if (id.includes('vue') || id.includes('@vue') || id.includes('@inertiajs')) {
                            return 'vendor-vue';
                        }
                        // Lucide icons - separate chunk due to large size
                        if (id.includes('lucide-vue-next')) {
                            return 'vendor-icons';
                        }
                        // UI libraries (Reka UI)
                        if (id.includes('reka-ui')) {
                            return 'vendor-ui';
                        }
                        // Charts library
                        if (id.includes('chart.js')) {
                            return 'vendor-charts';
                        }
                        // Table library
                        if (id.includes('@tanstack/vue-table')) {
                            return 'vendor-table';
                        }
                        // VueUse utilities
                        if (id.includes('@vueuse')) {
                            return 'vendor-vueuse';
                        }
                        // Other vendor dependencies
                        return 'vendor-other';
                    }
                },
            },
        },
        chunkSizeWarningLimit: 700,
    },
});
