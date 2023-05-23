let mix = require('laravel-mix');
const path = require('path');
const fse = require('fs-extra');
/*
 |--------------------------------------------------------------------------
 | Mix Asset Management
 |--------------------------------------------------------------------------
 |
 | Mix provides a clean, fluent API for defining some Webpack build steps
 | for your Laravel application. By default, we are compiling the Sass
 | file for the application as well as bundling up all the JS files.
 |
 */
// mix.setPublicPath('public/js');
var public_path = "";
if (process.env.NODE_ENV == "development") {
    public_path = "/";
} else if (process.env.NODE_ENV == "production") {
    public_path = "/";
}
mix
    .webpackConfig({
        output: {
            filename:'[name].js',
            chunkFilename: 'js/chunks/[name].[contenthash].js?id=[chunkhash]',
            publicPath: public_path,
        },
    })
    .version()
    .options({
        postCss: [
            require('autoprefixer'),
        ],
    });
mix.js('resources/assets/js/app.js', 'public/js')
    .extract(["vue"]).version();
//     .then(() => {
//         fse.pathExists(path.resolve(__dirname, '../js'), (err, exists) => {
//             if (exists) {
//                 fse.remove(path.resolve(__dirname, '../js'), err => {
//                     if (err) return console.error(err);
//                     moveNewFiles();
//                     console.log('success!')
//                 });
//             } else {
//                 moveNewFiles();
//             }
//         })
//     });
//
// function moveNewFiles () {
//     fse.copySync('public/js', path.resolve(__dirname, '../js'));
//     fse.copySync('public/mix-manifest.json', path.resolve(__dirname, '../mix-manifest.json'));
//     fse.removeSync(path.resolve(__dirname, './public'));
// }