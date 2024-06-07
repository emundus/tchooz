const path = require('path');

module.exports = {
    pages: {
        index: {
            entry: './vue/src/main.js',
        }
    },
    lintOnSave: false,
    outputDir: path.resolve(__dirname, '../../media/mod_emundus_dashboard_vue'),
    assetsDir: '../mod_emundus_dashboard_vue',

    css: {
        modules: false,
        extract: {
            filename: 'app.css',
            chunkFilename: '[name].css',
        },
    },
    configureWebpack: {
        output: {
            filename: 'app.js',
            chunkFilename: '[name].js',
        },
    },
};
