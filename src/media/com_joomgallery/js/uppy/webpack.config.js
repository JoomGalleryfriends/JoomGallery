const path = require('path');

module.exports = {
  target: 'web',
  //mode: 'production',
  mode: 'development',
  entry: './src/index.js',
  output: {
    path: path.resolve(__dirname, 'dist'),
    filename: 'uppy-uploader.js',
  },
  devtool: 'source-map',
}
