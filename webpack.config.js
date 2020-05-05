'use strict';

const path = require('path');
const { VueLoaderPlugin } = require('vue-loader');

module.exports = {
    mode: "production",
    entry: {
        'admin/schedule': './app/assets/admin/schedule/main.js',
        'web/schedule': './app/assets/web/schedule/main.js'
    },
    output: {
        filename: '[name]/main.min.js',
        path: path.resolve(__dirname, 'www/dist')
    },
    module: {
        rules: [
            {
                test: /\.vue$/,
                use: 'vue-loader'
            },
            {
                test: /\.scss$/,
                use: [
                    'vue-style-loader',
                    'css-loader',
                    'sass-loader'
                ]
            },
            {
                test: /\.js$/,
                use: ['source-map-loader'],
                enforce: 'pre'
            }
        ]
    },
    plugins: [
        new VueLoaderPlugin()
    ],
    devtool: 'source-map'
};