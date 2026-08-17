const path = require('path');

module.exports = {
  target: 'web',
  //mode: 'production',
  mode: 'development',
  entry: './src/index.js',
  output: {
    path: path.resolve(__dirname, 'dist'),
    filename: 'migrator.js',
    library: 'Migrator',
    libraryTarget: 'var'
  },
  devtool: 'source-map',
}
