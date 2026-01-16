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
        project:  'tsconfig.json',
    },
    env: {
        browser: true,
        es2022: true,
        node: true,
    },
    rules: {
        '@typescript-eslint/no-unused-vars': [
            'error',
            {
                argsIgnorePattern: '^_',
                varsIgnorePattern: '^_',
            },
        ],
        curly: ['error', 'all'],
        'newline-before-return': 'error',
        'vue/component-name-in-template-casing': ['error', 'PascalCase'],
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
};
