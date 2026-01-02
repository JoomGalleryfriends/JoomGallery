const path = require('path');

module.exports = {
  target: 'web',
  mode: 'production',
  //mode: 'development',
  entry: './src/index.js',
  output: {
    path: path.resolve(__dirname, 'dist'),
    filename: 'tasks.js',
    library: 'Tasks',
    libraryTarget: 'var'
  },
  devtool: 'source-map',
  resolve: {
    fallback: {
      events: require.resolve('events/'),
    },
  },
}
