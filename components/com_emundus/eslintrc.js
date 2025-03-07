/* eslint-env node */
module.exports = {
  root: true,
  env: {
    browser: true,
    node: true,
  },
  extends: ['plugin:vue/vue3-recommended', 'eslint:recommended', 'prettier'],
  parserOptions: {
    ecmaVersion: 'latest',
  },
  rules: {
    'prettier/prettier': ['error'],
    'vue/require-default-prop': 0,
    'vue/html-indent': ['error', 2],
    "indent": ['error', 4, { "SwitchCase": 1 }],
    'vue/singleline-html-element-content-newline': 0,
    'vue/component-name-in-template-casing': ['error', 'PascalCase'],
  },
  plugins: ['prettier'],
  languageOptions: {
    ecmaVersion: 'latest',
    sourceType: 'script',
  },
};
