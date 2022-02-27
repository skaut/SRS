'use strict';

const path = require('path');
const { VueLoaderPlugin } = require('vue-loader');
const { ProvidePlugin, DefinePlugin } = require('webpack');
const MiniCssExtractPlugin = require('mini-css-extract-plugin');

module.exports = {
    entry: {
        'admin': './app/assets/admin/main.js',
        'web': './app/assets/web/main.js',
        'install': './app/assets/install/main.js',
    },
    output: {
        filename: '[name]/bundle.js',
        path: path.resolve(__dirname, 'www/dist'),
        clean: true
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
            filename: '[name]/bundle.css',
        }),
        // new (require('webpack-bundle-analyzer').BundleAnalyzerPlugin),
    ],
    module: {
        rules: [
            {
                test: /\.vue$/,
                use: 'vue-loader'
            },
            {
                test: /\.js$/,
                use: 'babel-loader'
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
    }
};