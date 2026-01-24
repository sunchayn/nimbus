import type { StorybookConfig } from '@storybook/vue3-vite';
import path from 'node:path';

const config: StorybookConfig = {
    stories: [
        '../resources/js/components/**/*.stories.@(js|jsx|ts|tsx|mdx)',
    ],
    addons: [
        '@storybook/addon-essentials',
        '@storybook/addon-interactions',
        '@storybook/addon-a11y',
    ],
    framework: {
        name: '@storybook/vue3-vite',
        options: {},
    },
    viteFinal: async (config) => {
        config.resolve = config.resolve ?? {};
        config.resolve.alias = {
            ...config.resolve.alias,
            '@': path.resolve(__dirname, '../resources/js'),
            '~': path.resolve(__dirname, '../resources/css'),
        };

        return config;
    },
};

export default config;
