/* eslint-env node */
require('@rushstack/eslint-patch/modern-module-resolution');

module.exports = {
    root: true,
    extends: [
        'plugin:vue/vue3-recommended',
        'eslint:recommended',
        '@vue/eslint-config-typescript/recommended',
        '@vue/eslint-config-prettier',
        'plugin:prettier/recommended',
    ],
    parser: 'vue-eslint-parser',
    parserOptions: {
        ecmaVersion: 'latest',
        sourceType: 'module',
        parser: '@typescript-eslint/parser',
        project: 'tsconfig.json',
    },
    env: {
        browser: true,
        es2022: true,
        node: true,
    },
    plugins: ['import'],
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
        '@typescript-eslint/explicit-module-boundary-types': 'warn',
        '@typescript-eslint/consistent-type-imports': [
            'error',
            { prefer: 'type-imports', fixStyle: 'separate-type-imports' },
        ],

        /*
        * Code Style.
        */

        curly: ['error', 'all'],
        'import/no-relative-parent-imports': 'error',
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
                "singleQuote": true,
                "plugins": [
                    "prettier-plugin-organize-imports",
                    "prettier-plugin-tailwindcss"
                ],
                "printWidth": 90,
                "tabWidth": 4,
                "semi": true,
                "trailingComma": "all",
                "bracketSpacing": true,
                "arrowParens": "avoid",
                "proseWrap": "preserve",
                "vueIndentScriptAndStyle": false,
                "htmlWhitespaceSensitivity": "ignore"
            },
        ],
    },
    overrides: [
        {
            // Other folders still use function declarations, so the rule is limited to composables.
            files: ['resources/js/composables/**'],
            rules: { 'func-style': ['error', 'expression', { allowArrowFunctions: true }] },
        },
        {
            // Tests and configs import files that live outside resources/js.
            files: ['resources/js/tests/**'],
            rules: { 'import/no-relative-parent-imports': 'off' },
        },
    ],
};
