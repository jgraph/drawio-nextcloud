const path = require('path')
const webpack = require('webpack')

module.exports = {
    mode: 'production',
    entry: {
        'editor': './src/editor.js',
        'main': './src/main.js',
        'settings': './src/settings.js'
    },
    output: {
        filename: '[name].js',
        path: path.resolve(__dirname, 'js'),
        clean: true
    },
    module: {
        rules: [
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
        // fix "process is not defined" error:
        new webpack.ProvidePlugin({
            process: 'process/browser.js',
        }),
    ]
}
