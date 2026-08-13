import { fileURLToPath, URL } from 'node:url';
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import react from '@vitejs/plugin-react';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        tailwindcss(),
        laravel({
            input: 'resources/js/app.tsx',
            refresh: true,
        }),
        react(),
    ],
    resolve: {
        alias: {
            '@': fileURLToPath(new URL('./resources/js', import.meta.url)),
        },
    },
    build: {
        rollupOptions: {
            output: {
                manualChunks(id) {
                    if (id.includes('node_modules/react') || id.includes('node_modules/scheduler')) {
                        return 'react-vendor';
                    }
                    if (id.includes('node_modules')) {
                        if (id.includes('@inertiajs') || id.includes('ziggy-js')) {
                            return 'inertia';
                        }
                        if (id.includes('@radix-ui') || id.includes('sonner') || id.includes('lucide-react')) {
                            return 'ui';
                        }
                        return 'vendor';
                    }
                },
            },
        },
    },
});