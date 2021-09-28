'use strict';

const path = require('path');
const { VueLoaderPlugin } = require('vue-loader');
const { ProvidePlugin, DefinePlugin } = require('webpack');
const window = require('global/window');

module.exports = {
    mode: "production",
    entry: {
        'admin': './app/assets/admin/main.js',
        'web': './app/assets/web/main.js',
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
        new VueLoaderPlugin(),
        new ProvidePlugin({
            $: 'jquery',
            jQuery: 'jquery',
        }),
        new ProvidePlugin({
            naja: ['naja', 'default'],
        }),
        new DefinePlugin({
            ALERT_DURATION: 5000,
            ALERT_ANIMATION: 1000,
        })
    ],
    optimization: {
        minimize: false
    },
    devtool: 'source-map'
};