var elixir = require('laravel-elixir')
var convertNewline = require('gulp-convert-newline')

/*
 |--------------------------------------------------------------------------
 | Elixir Asset Management
 |--------------------------------------------------------------------------
 |
 | Elixir provides a clean, fluent API for defining some basic Gulp tasks
 | for your Laravel application. By default, we are compiling the Sass
 | file for our application, as well as publishing vendor resources.
 |
 */

gulp.task('fixNewLines', function() {
    gulp.src('public/js/app.js')
        .pipe(convertNewline({
            newline: 'lf'
        }))
        .pipe(gulp.dest('public/js'))
})

elixir(function(mix) {
    //mix.sass('app.scss');
    mix.browserify('app.js')
    mix.task('fixNewLines')
    mix.version(['public/js/app.js', 'public/css/main.css'])
})
