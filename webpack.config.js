const path      = require('path');
module.exports  = {
    entry: './src/index.js',
    mode: 'production',
    output: {
        path: path.resolve(__dirname, "docs/dist"),
        filename: 'bundle.min.js',
    },
    module: {
        rules: [
            {
                test: /\.(s(a|c)ss)$/,
                use: ['style-loader','css-loader', 'sass-loader']
            },
            {
                test: /\.html$/,
                use: [
                    {
                        loader: 'html-loader',
                        options: {}
                    }
                ]
            }
        ]
    },
};