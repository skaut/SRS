'use strict';

const path = require('path');
const { VueLoaderPlugin } = require('vue-loader');
const window = require('global/window');

module.exports = {
    mode: "production",
    entry: {
        'admin': './app/assets/admin/main.js',
        'admin-vendor': [
            'script-loader!jquery',
            'script-loader!bootstrap'
        ],
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
                test: /\.css$/i,
                use: [
                    'style-loader',
                    'css-loader'
                ]
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
    optimization: {
        minimize: false
    },
    devtool: 'source-map'
};