'use strict';

const path = require('path');
const { VueLoaderPlugin } = require('vue-loader');
const { ProvidePlugin, DefinePlugin } = require('webpack');
const MiniCssExtractPlugin = require('mini-css-extract-plugin');

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
                test: /\.js$/,
                use: 'source-map-loader',
                enforce: 'pre'
            },
            {
                test: /\.(css|scss|sass)$/,
                use: [
                    {
                        loader: MiniCssExtractPlugin.loader,
                        options: {
                            publicPath: '../'
                        }
                    },
                    'css-loader',
                    'postcss-loader'
                ],
            },
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
        }),
        new MiniCssExtractPlugin({
            filename: '[name]/bundle.min.css',
        }),
        // new (require('webpack-bundle-analyzer').BundleAnalyzerPlugin),
    ],
    optimization: {
        minimize: false
    },
    devtool: 'source-map'
};