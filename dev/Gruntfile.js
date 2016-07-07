module.exports = function(grunt) {
    grunt.option('stack', true);


  require('load-grunt-tasks')(grunt);

  var pkg = grunt.file.readJSON('package.json');

    // setting browser compatibility
    pkg.supportedBrowsers = ['> 5% in DE', 'ie 10'];

    // 1. All configuration goes here
    grunt.initConfig({
        pkg: grunt.file.readJSON('package.json'),

		less: {
            default : {
                options: {
                    compress: true,
                    yuicompress: true,
                    optimization: 2,
                    sourceMap : true,
                    // sourceMapFilename : 'plugins/osat/assets/css/styles.css.map',
                    sourceMapURL : './styles.css.map',
                    plugins: [
                        new (require('less-plugin-autoprefix'))({browsers: pkg.supportedBrowsers })
                    ]
                },
                files: [{
                    expand: true,     // Enable dynamic expansion.
                    cwd: './',      // Src matches are relative to this path.
                    src: ['**/_less/styles.less'], // Actual pattern(s) to match.
                    dest: './',   // Destination path prefix.
                    ext: '.css',   // Dest filepaths will have this extension.
                    extDot: 'last',   // Extensions in filenames begin after the first dot
                    rename: function(dest, src) {
                        src = src.replace(/\/_less\//, '/css/');
                        return src;
                    }
                }],
            }
		},

		concat: {
			options: {
				separator: ';\n',
				stripBanners: true,
                sourceMap: true
			}
		},

		uglify: {
			options: {
                sourceMap : true,
                sourceMapIncludeSources : true,
                sourceMapIn : function(path) { return path.replace(/\.js/,"\.js\.map")},
				compress: {
					// drop_console: false
				}
			}
		},

        shell: {
            default : {
                command: './rsync.sh'
            }
        },

		watch: {
			css: {
				files: ['**/_less/**/*.less'], // which files to watch
				tasks: ['less:default', 'shell:default']
			},
            rsync: {
              files: ['templates/**/*', '!templates/_*/**/*', 'plugins/**/*', '!plugins/_*/**/*'],
              tasks: ['shell:default']
            }
		}
    });

    // get all module directories
    grunt.file.expand('**/_js').forEach(function (dir) {
        // get the module name from the directory name
        var dirName = dir.substr(dir.lastIndexOf('/')+1),
            taskLabel = dir.replace(/[^a-zA-Z0-9\_]/g, '_');


        // get the current concat object from initConfig
        var concat = grunt.config.get('concat') || {};
        concat[taskLabel] = {
            src : [dir + '/**/*.js'],
            dest: dir + '/../js/scripts.js'
        };
        grunt.config.set('concat', concat);

        var uglify = grunt.config.get('uglify') || {};
        uglify[taskLabel] = {
            src: ['<%= concat.' + taskLabel + '.dest %>'],
            dest: '<%= concat.' + taskLabel + '.dest %>'
        };
        grunt.config.set('uglify', uglify);

        var watch = grunt.config.get('watch') || {};
        watch[taskLabel] = {
            files: ['<%= concat.' + taskLabel + '.src %>'],
            tasks: ['concat:'+taskLabel, 'uglify:'+taskLabel, 'shell:default']
        };
        grunt.config.set('watch', watch);
    });

    grunt.registerTask('default', ['concat', 'uglify', 'less', 'shell', 'watch']);

};
