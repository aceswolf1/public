var gulp            = require('gulp');
var browserSync     = require('browser-sync').create();
var sass            = require('gulp-sass');
var sassGlob        = require('gulp-sass-glob');
var autoprefixer    = require('gulp-autoprefixer');
var sourcemaps      = require('gulp-sourcemaps');
var concat          = require('gulp-concat');
var uglify          = require('gulp-uglify');

// Proxy Server + watching scss/html files
gulp.task('serve', ['sass'], function() {
    browserSync.init({
        proxy: "http://dev.webdesignofyork.com/hr-pharma/",
        open: false
    });

    gulp.watch("**/*.scss", ['sass']);
    gulp.watch(["modules/**/*.js", "js/main.js"], ['scripts']).on('change', browserSync.reload);
    gulp.watch("**/*.php").on('change', browserSync.reload);
});

gulp.task('scripts', function() {
  return gulp.src(["./modules/**/*.js", "./js/main.js"])
    .pipe(concat('all.js'))
    // .pipe(uglify())
    .pipe(gulp.dest('./js/'));
});

// Compile sass into CSS & auto-inject into browsers
gulp.task('sass', function() {
    return gulp.src("./scss/**/*.scss")
        .pipe(sourcemaps.init())
        .pipe(sassGlob())
        .pipe(sass().on('error', sass.logError))
        .pipe(autoprefixer({
            browsers: ['last 2 versions'],
            cascade: false
        }))
        .pipe(sourcemaps.write())
        .pipe(gulp.dest("./css"))
        .pipe(browserSync.stream())
        .on('error', function(error) {
            console.error('' + error);
        })
});

gulp.task('default', ['scripts', 'serve']);
