var gulp = require('gulp');

var del = require('del');
gulp.task('clean:min-css', function () {
	// Delete all *.min.css files in the CSS directory so we don't get duplicates
	return del([
		'css/*.min.css'
	]);
});

var foreach = require('gulp-foreach');
var path = require('path');
var concatCSS = require('gulp-concat-css');
var autoprefixer = require('gulp-autoprefixer');
var cssnano = require('gulp-cssnano');
gulp.task('default', ['clean:min-css'], function() {
	gulp.src('css/*.css')
		.pipe(
			// Loop over each stream, figure out the filename, and run the stream through concatCSS() passing along the dynamic filename
			foreach(function(stream, file) {
				// Get the filename without the extension...
				var filename = path.basename(file.path, '.css');

				return stream.pipe( concatCSS(filename + '.min.css') )
			})
		)

		// Autoprefix
		.pipe( autoprefixer() )

		// minify CSS
		.pipe( cssnano() )

		// Save out the new file
		.pipe( gulp.dest('css/') );
});
