var gulp          = require('gulp');
var browserSync   = require('browser-sync').create();
var $             = require('gulp-load-plugins')();
var autoprefixer  = require('autoprefixer');
const cleanCSS = require('gulp-clean-css');
var sassGlob = require('gulp-sass-glob');
var sourcemaps = require('gulp-sourcemaps');


var sassPaths = [
  'node_modules/foundation-sites/scss',
  'node_modules/motion-ui/src'
];

function sass() {
  return gulp.src('scss/style.scss')
  .pipe(sourcemaps.init())
    .pipe(sassGlob()) //this was what I was missing
    .pipe($.sass({
      includePaths: sassPaths,
    outputStyle: 'expanded'
    })
      .on('error', $.sass.logError))

    .pipe($.postcss([
      autoprefixer()
    ]))
    // .pipe(cleanCSS({compatibility: 'ie8'}))
    .pipe(sourcemaps.write('.'))
    .pipe(gulp.dest('.'))
    .pipe(browserSync.stream());
};

function serve() {
  browserSync.init({
    notify: true,
    proxy: 'https://bbptest.local:8890',
    https: true
  });

  gulp.watch("scss/**/*.scss", sass);
  gulp.watch("*.html").on('change', browserSync.reload);
}

gulp.task('sass', sass);
gulp.task('serve', gulp.series('sass', serve));
gulp.task('default', gulp.series('sass', serve));




