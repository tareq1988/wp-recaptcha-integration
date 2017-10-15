var gulp = require('gulp');
var gulputil = require('gulp-util');
var concat = require('gulp-concat');
var uglify = require('gulp-uglify');
var sass = require('gulp-sass');
var nodesass = require('node-sass');
var sourcemaps = require('gulp-sourcemaps');
var rename = require('gulp-rename');
var autoprefixer = require('gulp-autoprefixer');
var pngquant = require('gulp-pngquant');
var fs = require("fs");
var btoa = require('btoa');

// handle scss inline-image
var sassFunctions = {
	'inline-image'	: function( src, mime, b64, done ) {
		var src = src.getValue();
		var mime = ('object' === typeof mime) ? mime.getValue() : 'image/'+src.split('.').pop();
		var b64	= ('object' === typeof b64) ? b64.getValue() : true;
		var content = fs.readFileSync(src);
		var ret = 'data:' + mime + ';';
		if ( b64 ) {
			ret += 'base64,';
			ret += btoa(content);
		} else {
			ret += content;
		}
		return new nodesass.types.String(ret);
	}
}



gulp.task('styles-admin', function(){
	gulp.src( './src/scss/recaptcha-options.scss' )
		.pipe( sourcemaps.init() )
		.pipe( sass( {
			functions: sassFunctions,
			outputStyle: 'nested'
		} ).on('error', sass.logError) )
		.pipe( autoprefixer( {
            browsers: ['last 3 versions'],
            cascade: false
        } ) )
		.pipe( gulp.dest( './css/' ) )
		.pipe( sass( {
			functions: sassFunctions,
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


gulp.task( 'pngquant', function() {
    gulp.src('./src/images/*.png')
        .pipe( pngquant({
            quality: '65-80'
        }))
        .pipe(gulp.dest('./src/images/compressed/'));
});

gulp.task( 'watch', function() {
	gulp.watch('./src/scss/**/*.scss', ['styles-admin'] );
	gulp.watch('./src/js/**/*.js', ['scripts-admin'] );
	gulp.watch('./src/images/*.png', ['pngquant'] );
} );


gulp.task( 'build', ['pngquant', 'styles-admin','scripts-admin'] );

gulp.task( 'default', ['build','watch'] );
