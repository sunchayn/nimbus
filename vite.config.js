import tailwindcss from '@tailwindcss/vite';
import vue from '@vitejs/plugin-vue';
import laravel from 'laravel-vite-plugin';
import path from 'node:path';
import { defineConfig } from 'vite';

export default defineConfig(({ mode }) => ({
    base: '/vendor/nimbus/',
    plugins: [
        vue(),
        tailwindcss(),
        laravel({
            input: ['resources/js/app/app.ts', 'resources/css/app.css'],
            buildDirectory: './',
            publicDirectory: 'resources/dist',
        }),
    ],
    server: {
        host: 'localhost',
        port: 5174, // <- Use a unique port to avoid conflicts
        cors: {
            origin: '*',
        },
    },
    resolve: {
        alias: {
            '@': path.resolve(__dirname, '/resources/js'),
            '~': path.resolve(__dirname, '/resources/css'),
        },
    },
    define: {
        'process.env.NODE_ENV': JSON.stringify(
            mode === 'development' ? 'development' : 'production',
        ),
    },
}));
