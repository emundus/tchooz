const path = require('path');

module.exports = {
    lintOnSave: false,
    outputDir: path.resolve(__dirname, '../../media/mod_emundus_filters'),
    assetsDir: '../mod_emundus_filters',

    css: {
        requireModuleExtension: false,
        extract: {
            filename: '[name].css',
            chunkFilename: '[name].css',
        },
    },
    configureWebpack: {
        output: {
            filename: '[name].js',
            chunkFilename: '[name].js',
        },
    },
};
