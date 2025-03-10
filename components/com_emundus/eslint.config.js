import vue from 'eslint-plugin-vue';
import vueParser from 'vue-eslint-parser';
import prettier from 'eslint-plugin-prettier';
import prettierConfig from 'eslint-config-prettier';

export default [
    {
        ignores: ['node_modules/', 'dist/'],
    },
    {
        files: ['**/*.vue', '**/*.js'],
        languageOptions: {
            parser: vueParser,
            ecmaVersion: 'latest',
            sourceType: 'module',
        },
        plugins: { prettier },
        rules: {
            'no-console': 'off',
            'indent': ['error', 2],
            'vue/html-indent': ['error', 2],
            ...prettierConfig.rules,
            'prettier/prettier': 'error',
            'vue/multi-word-component-names': 'off'
        }
    }
];
