const mix = require('laravel-mix')

mix.js('resources/js/app.js', 'public/js')
.stylus('resources/stylus/app.styl', 'public/css')
.disableSuccessNotifications()
.webpackConfig({
	resolve: {
		alias: {
			'~Components': path.resolve(__dirname, 'resources/js/Components'),
			'~Echo': path.resolve(__dirname, 'resources/js/Echo'),
			'~Games': path.resolve(__dirname, 'resources/js/Games'),
			'~Pages': path.resolve(__dirname, 'resources/js/Pages'),
			'~Store': path.resolve(__dirname, 'resources/js/Store'),
		},
	},
})

if (mix.inProduction()) {
	mix.version()
}
