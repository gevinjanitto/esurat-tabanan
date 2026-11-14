import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js', 'resources/js/login.js', 'resources/js/pdf-viewer.js', 'resources/js/annotator.js'],
            refresh: true,
        }),
        tailwindcss(),
    ],
    build: { target: 'es2022', chunkSizeWarningLimit: 1500 },
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
