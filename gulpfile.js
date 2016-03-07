var gulp = require('gulp');
var del = require('del');
var importCSS = require('gulp-import-css');
var rename = require('gulp-rename');
var cssnano = require('gulp-cssnano');

gulp.task('clean:min-css', function () {
    // Delete all *.min.css files in the CSS directory so we don't get duplicates
    return del([
        'css/*.min.css'
    ]);
});

gulp.task('default', ['clean:min-css'], function() {
    gulp.src('css/*.css')
        // Munge contents of @import statements into one file
        .pipe( importCSS() )

        // minify CSS
        .pipe( cssnano() )

        // rename the extention to .min.css
        .pipe( rename( function(path) {
            path.extname = '.min.css';
            return path;
        }) )

        // Save out the new file
        .pipe( gulp.dest('css/') );
});
