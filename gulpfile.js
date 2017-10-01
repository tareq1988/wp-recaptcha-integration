var gulp = require('gulp');
var gulputil = require('gulp-util');
var concat = require('gulp-concat');
var uglify = require('gulp-uglify');
var sass = require('gulp-sass');
var sourcemaps = require('gulp-sourcemaps');
var rename = require('gulp-rename');
var autoprefixer = require('gulp-autoprefixer');



gulp.task('styles-admin', function(){
	gulp.src( './src/scss/recaptcha-options.scss' )
		.pipe( sourcemaps.init() )
		.pipe( sass( {
			outputStyle: 'nested'
		} ).on('error', sass.logError) )
		.pipe( autoprefixer( {
            browsers: ['last 3 versions'],
            cascade: false
        } ) )
		.pipe( gulp.dest( './css/' ) )
		.pipe( sass( {
			outputStyle: 'compressed'
		} ).on('error', sass.logError) )
		.pipe( autoprefixer( {
            browsers: ['last 3 versions'],
            cascade: false
        } ) )
		.pipe( rename( { suffix:'.min' } ) )
		.pipe( sourcemaps.write() )
		.pipe( gulp.dest( './css/' ) );
});

gulp.task('scripts-admin', function() {
	gulp.src( './src/js/recaptcha-options.js' )
		.pipe( gulp.dest( './js/' ) )
		.pipe( sourcemaps.init() )
		.pipe( uglify().on('error', gulputil.log ) )
		.pipe( rename( { suffix:'.min' } ) )
		.pipe( sourcemaps.write() )
		.pipe( gulp.dest( './js/' ) );

	gulp.src( './src/js/wpcf7.js' )
		.pipe( gulp.dest( './js/' ) )
		.pipe( sourcemaps.init() )
		.pipe( uglify().on('error', gulputil.log ) )
		.pipe( rename( { suffix:'.min' } ) )
		.pipe( sourcemaps.write() )
		.pipe( gulp.dest( './js/' ) );
});


gulp.task( 'watch', function() {
	gulp.watch('./src/scss/**/*.scss', ['styles-admin'] );
	gulp.watch('./src/js/**/*.js', ['scripts-admin'] );
} );


gulp.task( 'build', ['styles-admin','scripts-admin'] );

gulp.task( 'default', ['build','watch'] );
