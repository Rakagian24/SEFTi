import vue from '@vitejs/plugin-vue';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';
import { defineConfig } from 'vite';
import fs from 'fs';
import path from 'path';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/js/app.ts'],
            ssr: 'resources/js/ssr.ts',
            refresh: true,
        }),
        tailwindcss(),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
    ],
    server: {
        allowedHosts: ['f545c52fdf12.ngrok-free.app'],
        cors: true,
        host: '0.0.0.0', // Ganti true menjadi '0.0.0.0'
        https: {
            key: fs.readFileSync(path.resolve(__dirname, 'localhost-key.pem')),
            cert: fs.readFileSync(path.resolve(__dirname, 'localhost.pem')),
        },
        origin: 'https://f545c52fdf12.ngrok-free.app',
    },
});
