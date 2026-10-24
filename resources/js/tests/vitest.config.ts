import ui from '@nuxt/ui/vite';
import vue from '@vitejs/plugin-vue';
import path from 'node:path';
import { defineConfig } from 'vitest/config';
import { nuxtUiOptions } from '../../../vite.config.js';

export default defineConfig({
    plugins: [vue(), ui(nuxtUiOptions)],
    test: {
        globals: true,
        environment: 'jsdom',
        setupFiles: [path.resolve(__dirname, './setup.ts')],
        include: [path.resolve(__dirname, '../**/*.{test,spec}.{js,ts,vue}')],
        exclude: [
            path.resolve(__dirname, '../../node_modules'),
            path.resolve(__dirname, '../../dist'),
            path.resolve(__dirname, '../../.idea'),
            path.resolve(__dirname, '../../.git'),
            path.resolve(__dirname, '../../.cache'),
        ],
        coverage: {
            provider: 'v8',
            reporter: ['text', 'json', 'html', 'lcov'],
            reportsDirectory: path.resolve(__dirname, '../../coverage'),
            include: [path.resolve(__dirname, '../**/*.{js,ts,vue}')],
            exclude: [
                path.resolve(__dirname, '../**/*.d.ts'),
                path.resolve(__dirname, '../**/*.config.{js,ts}'),
                path.resolve(__dirname, '../**/index.ts'),
                path.resolve(__dirname, './**'),
                path.resolve(__dirname, '../**/*.test.{js,ts,vue}'),
                path.resolve(__dirname, '../**/*.spec.{js,ts,vue}'),
                path.resolve(__dirname, '../app/app.ts'),
                path.resolve(__dirname, '../app/router.ts'),
                path.resolve(__dirname, '../types/**'),
            ],
            thresholds: {
                global: {
                    branches: 90,
                    functions: 90,
                    lines: 90,
                    statements: 90,
                },
            },
        },
        snapshotFormat: {
            escapeString: true,
            printBasicPrototype: false,
        },
        server: {
            deps: {
                inline: ['codemirror-json-schema'],
            },
        },
    },
    resolve: {
        alias: {
            '@': path.resolve(__dirname, '../'), // resources/js
            '~': path.resolve(__dirname, '../../css'), // resources/css
        },
    },
});
