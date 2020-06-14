 mix = require('laravel-mix');

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


// mix.js('resources/js/app.js', 'js')
//     .sass('resources/sass/app.scss', 'css');

Mix.listen('configReady', (webpackConfig) => {
    // Exclude 'svg' folder from font loader
    let fontLoaderConfig = webpackConfig.module.rules.find(rule => String(rule.test) === String(/(\.(png|jpe?g|gif|webp)$|^((?!font).)*\.svg$)/));
    fontLoaderConfig.exclude = /(Modules\/Admin\/Resources\/assets\/vue-element-admin\/src\/icons)/;
});

mix.js('Modules/Admin/Resources/assets/vue-element-admin/src/main.js', 'js')
    // .sass('resources/sass/app.scss','css')
    // .extract(['vue','lodash', 'axios','vuex', 'vue-router','element-ui'])
    .setPublicPath('public/admin')
    .webpackConfig({
        resolve: {
            alias: {
                '@': path.resolve(__dirname, 'Modules/Admin/Resources/assets/vue-element-admin/src'),
            },
        },
        output: {
            filename:'[name].js',
            publicPath: '/',
            chunkFilename: 'js/chunk/[name].[chunkhash].chunk.js'
        },
        module: {
            rules: [
                {
                    test: /\.svg$/,
                    loader: 'svg-sprite-loader',
                    include: [path.resolve(__dirname, '/')],
                    options: {
                        symbolId: 'icon-[name]'
                    },
                }
            ],
        },
        /*optimization: {
            splitChunks: {
                chunks: 'all',
                cacheGroups: {
                    libs: {
                        filename:'[name].js',
                        name: 'chunk-libs',
                        test: /[\\/]node_modules[\\/]/,
                        priority: 10,
                        chunks: 'initial' // only package third parties that are initially dependent
                    },
                    elementUI: {
                        filename:'[name].js',
                        name: 'chunk-elementUI', // split elementUI into a single package
                        priority: 20, // the weight needs to be larger than libs and app or it will be packaged into libs or app
                        test: /[\\/]node_modules[\\/]_?element-ui(.*)/ // in order to adapt to cnpm
                    },
                    commons: {
                        filename:'[name].js',
                        name: 'chunk-commons',
                        // test: resolve('Modules/Admin/Resources/assets/vue-element-admin/src/components'), // can customize your rules
                        minChunks: 3, //  minimum common number
                        priority: 5,
                        reuseExistingChunk: true
                    }
                }
            }
        }*/
    })
    .version()
    .disableNotifications();

/*mix.options({
    extractVueStyles: 'css/vue.css', // Extract .vue component styling to file, rather than inline.
    processCssUrls: false
});*/

if (!mix.inProduction()) {
    mix.webpackConfig({
        devtool: 'source-map',
        // devtool: 'eval-source-map',
    });
    dianchi
    /**
     *下面方法启用 bs，不传参则使用 laravel-mix 的默认配置
     * 根据实际使用环境配置参数以获得更好体验
     * bs 配置选项参考 https://www.browsersync.io/docs/options
     */
    /*mix.browserSync({
        proxy: 'laravel-mix-autoreload-demo.test/',
        startPath: '/demo-bs',
        open: true,
        reloadOnRestart: true,
        watchOptions: {
            usePolling: true,
        },
    });*/

}

