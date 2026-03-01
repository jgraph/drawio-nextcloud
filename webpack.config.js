const path = require('path')
const webpack = require('webpack')
const { VueLoaderPlugin } = require('vue-loader')

module.exports = {
    mode: 'production',
    entry: {
        'editor': './src/editor.js',
        'main': './src/main.js',
        'settings': './src/settings.js',
        'drawio-reference': './src/reference.js'
    },
    output: {
        filename: '[name].js',
        path: path.resolve(__dirname, 'js'),
        clean: true
    },
    module: {
        rules: [
            {
                test: /\.vue$/,
                loader: 'vue-loader'
            },
            {
                test: /\.s?(a|c)ss$/,
                use: [
                    'style-loader',
                    'css-loader',
                    'sass-loader'
                ]
            },
        ]
    },
    plugins: [
        new VueLoaderPlugin(),
        // fix "process is not defined" error:
        new webpack.ProvidePlugin({
            process: 'process/browser.js',
        }),
    ]
}
