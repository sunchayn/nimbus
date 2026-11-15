import js from '@eslint/js';
import vueTsEslintConfig, {
    defineConfigWithVueTs,
} from '@vue/eslint-config-typescript';
import skipFormatting from '@vue/eslint-config-prettier/skip-formatting';
import importX from 'eslint-plugin-import-x';
import prettier from 'eslint-plugin-prettier/recommended';
import pluginVue from 'eslint-plugin-vue';
import globals from 'globals';

export default defineConfigWithVueTs(
    {
        ignores: [
            '**/node_modules/**',
            '**/vendor/**',
            'resources/dist/**',
            'resources/**/*.snap',
            'build/**',
            'coverage/**',
            '**/*.min.js',
            '**/*.min.css',
        ],
    },
    js.configs.recommended,
    pluginVue.configs['flat/recommended'],
    vueTsEslintConfig(),
    prettier,
    skipFormatting,
    {
        files: ['resources/js/**/*.{js,ts,vue}'],
        languageOptions: {
            ecmaVersion: 'latest',
            sourceType: 'module',
            globals: { ...globals.browser, ...globals.node, ...globals.es2022 },
        },
        plugins: { 'import-x': importX },
        rules: {
            /*
             * TypeScript.
             */

            '@typescript-eslint/no-unused-vars': [
                'error',
                {
                    argsIgnorePattern: '^_',
                    varsIgnorePattern: '^_',
                },
            ],
            '@typescript-eslint/no-explicit-any': 'error',
            // Components declare an empty Props interface when they take no props.
            '@typescript-eslint/no-empty-object-type': [
                'error',
                { allowInterfaces: 'always' },
            ],
            '@typescript-eslint/explicit-module-boundary-types': 'warn',
            '@typescript-eslint/consistent-type-imports': [
                'error',
                { prefer: 'type-imports', fixStyle: 'separate-type-imports' },
            ],

            /*
             * Code Style.
             */

            curly: ['error', 'all'],
            'import-x/no-relative-parent-imports': 'error',
            'newline-before-return': 'error',

            /*
             * Vue-specific Rules.
             */

            'vue/component-name-in-template-casing': ['error', 'PascalCase'],
            'vue/define-emits-declaration': ['error', 'type-based'],
            'vue/define-props-declaration': ['error', 'type-based'],
            'vue/block-lang': ['error', { script: { lang: 'ts' } }],
            'vue/component-api-style': ['error', ['script-setup']],

            /*
             * Prettier.
             */

            'prettier/prettier': [
                'error',
                {
                    singleQuote: true,
                    plugins: [
                        'prettier-plugin-organize-imports',
                        'prettier-plugin-tailwindcss',
                    ],
                    printWidth: 90,
                    tabWidth: 4,
                    semi: true,
                    trailingComma: 'all',
                    bracketSpacing: true,
                    arrowParens: 'avoid',
                    proseWrap: 'preserve',
                    vueIndentScriptAndStyle: false,
                    htmlWhitespaceSensitivity: 'ignore',
                },
            ],
        },
    },
    {
        // Other folders still use function declarations, so the rule is limited to composables.
        files: ['resources/js/composables/**'],
        rules: { 'func-style': ['error', 'expression', { allowArrowFunctions: true }] },
    },
    {
        // Tests and configs import files that live outside resources/js.
        files: ['resources/js/tests/**'],
        rules: { 'import-x/no-relative-parent-imports': 'off' },
    },
);
