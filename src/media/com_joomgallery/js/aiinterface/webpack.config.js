const path = require('path');

module.exports = {
  target: 'web',
  //mode: 'production',
  mode: 'development',
  entry: './src/index.js',
  devtool: 'source-map',

  experiments: {
    outputModule: true,
  },

  output: {
    path: path.resolve(__dirname, 'dist'),
    filename: 'aiinterface.js',
    module: true,
    //library: 'AIinterface',
    //libraryTarget: 'var'
  },

  externalsType: 'module',
  externals: {
    'joomla.dialog': 'joomla.dialog',
    'bootstrap.modal': 'bootstrap.modal',
  },
}
