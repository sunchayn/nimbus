import ui from '@nuxt/ui/vite';
import vue from '@vitejs/plugin-vue';
import laravel from 'laravel-vite-plugin';
import path from 'node:path';
import { defineConfig } from 'vite';
import preset, { twMergeConfig } from './tools/ui/theme.js';

/*
 * Component theme preset for Nuxt UI, defined in tools/ui/theme.js.
 */
export const uiThemePreset = preset;

/**
 * Nuxt UI's own icons, mapped to HugeIcons.
 * Every key of the Nuxt UI defaults is listed, because a missing key keeps the default name, which would be fetched from the network.
 */
export const nuxtUiIcons = {
    arrowDown: 'i-hugeicons-arrow-down-02',
    arrowLeft: 'i-hugeicons-arrow-left-02',
    arrowRight: 'i-hugeicons-arrow-right-02',
    arrowUp: 'i-hugeicons-arrow-up-02',
    caution: 'i-hugeicons-alert-circle',
    check: 'i-hugeicons-tick-02',
    chevronDoubleLeft: 'i-hugeicons-arrow-left-double',
    chevronDoubleRight: 'i-hugeicons-arrow-right-double',
    chevronDown: 'i-hugeicons-arrow-down-01',
    chevronLeft: 'i-hugeicons-arrow-left-01',
    chevronRight: 'i-hugeicons-arrow-right-01',
    chevronUp: 'i-hugeicons-arrow-up-01',
    close: 'i-hugeicons-cancel-01',
    copy: 'i-hugeicons-copy-01',
    copyCheck: 'i-hugeicons-copy-check',
    dark: 'i-hugeicons-moon-02',
    drag: 'i-hugeicons-drag-drop-vertical',
    ellipsis: 'i-hugeicons-more-horizontal',
    error: 'i-hugeicons-cancel-circle',
    external: 'i-hugeicons-arrow-up-right-01',
    eye: 'i-hugeicons-view',
    eyeOff: 'i-hugeicons-view-off-slash',
    file: 'i-hugeicons-file-01',
    folder: 'i-hugeicons-folder-01',
    folderOpen: 'i-hugeicons-folder-open',
    hash: 'i-hugeicons-hashtag',
    info: 'i-hugeicons-information-circle',
    light: 'i-hugeicons-sun-03',
    loading: 'i-hugeicons-loading-03',
    menu: 'i-hugeicons-menu-01',
    minus: 'i-hugeicons-minus-sign',
    panelClose: 'i-hugeicons-sidebar-left-01',
    panelOpen: 'i-hugeicons-sidebar-left',
    plus: 'i-hugeicons-plus-sign',
    reload: 'i-hugeicons-reload',
    search: 'i-hugeicons-search-01',
    stop: 'i-hugeicons-stop',
    star: 'i-hugeicons-star',
    success: 'i-hugeicons-checkmark-circle-02',
    system: 'i-hugeicons-computer',
    tip: 'i-hugeicons-bulb',
    upload: 'i-hugeicons-upload-04',
    warning: 'i-hugeicons-alert-02',
};

/**
 * Nuxt UI plugin options, shared with the Vitest config.
 * Components are imported explicitly, so auto-imports stay off and no d.ts files are generated.
 * Tailwind is registered by this plugin, so it must not be added separately.
 *
 * @type {import('@nuxt/ui/vite').NuxtUIOptions}
 */
export const nuxtUiOptions = {
    colorMode: false,
    components: false,
    autoImport: false,
    dts: false,
    theme: {
        colors: ['primary', 'secondary', 'error', 'success', 'warning', 'info'],
    },
    ui: {
        colors: {
            primary: 'zinc',
            secondary: 'violet',
            neutral: 'zinc',
            error: 'rose',
            success: 'emerald',
            warning: 'amber',
            info: 'blue',
        },
        icons: nuxtUiIcons,
        tv: { twMergeConfig },
        ...uiThemePreset,
    },
    icon: {
        // The default scan skips .ts files, so icon names in TypeScript would be missed.
        clientBundle: {
            // Listing the names here makes the build fail when one cannot be resolved.
            icons: Object.values(nuxtUiIcons),
            scan: {
                globInclude: ['resources/js/**/*.{vue,ts}'],
                globExclude: ['node_modules', 'resources/js/tests/**'],
            },
        },
    },
};

export default defineConfig(({ mode }) => ({
    base: '',
    plugins: [
        vue(),
        ui(nuxtUiOptions),
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
            '@': path.resolve(__dirname, 'resources/js'),
            '~': path.resolve(__dirname, 'resources/css'),
        },
    },
    define: {
        'process.env.NODE_ENV': JSON.stringify(
            mode === 'development' ? 'development' : 'production',
        ),
    },
}));
