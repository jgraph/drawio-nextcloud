const path = require('path')

module.exports = {
    mode: 'production',
    entry: {
        'editor': './src/editor.js',
        'main': './src/main.js',
        'settings': './src/settings.js'
    },
    output: {
        filename: '[name].js',
        path: path.resolve(__dirname, 'drawio/js')
    },
    module: {
        rules: [
            {
                test: /\.s(a|c)ss$/,
                use: [
                    'style-loader',
                    'css-loader',
                    'sass-loader'
                ]
            }
        ]
    }
}
