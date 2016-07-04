module.exports = function(grunt) {

  require('load-grunt-tasks')(grunt);

  var pkg = grunt.file.readJSON('package.json');

    // setting browser compatibility
    pkg.supportedBrowsers = ['> 5% in DE', 'ie 10'];
    pkg.theme = 'osat';
    pkg.folders = {
        'template'  : '../templates/' + pkg.theme,
        'upload'    : '../upload/templates/' + pkg.theme,
        'plugins'    : '../plugins',
        'plugin'    : '../plugins/' + pkg.theme,
        'pluginlogin'    : '../plugins/login',
        'thirdparty': '../third_party/' + pkg.theme,
        'temmplatecache' : '../tmp/assets/*/'
    };

    // 1. All configuration goes here
    grunt.initConfig({
        pkg: grunt.file.readJSON('package.json'),

		less: {
            template : {
    			options: {
    				compress: false,
    				yuicompress: false,
    				optimization: 2,
                    sourceMap : true,
                    sourceMapFilename : 'template/css/styles.css.map',
                    sourceMapURL : './styles.css.map',
    				plugins: [
    					new (require('less-plugin-autoprefix'))({browsers: pkg.supportedBrowsers })
    				]
    			},
    			files: {
    				'template/css/styles.css' : 'template/_less/styles.less'
    			}
            },
            plugin : {
    			options: {
    				compress: true,
    				yuicompress: true,
    				optimization: 2,
                    sourceMap : true,
                    sourceMapFilename : 'plugins/osat/assets/css/styles.css.map',
                    sourceMapURL : './styles.css.map',
    				plugins: [
    					new (require('less-plugin-autoprefix'))({browsers: pkg.supportedBrowsers })
    				]
    			},
    			files: {
    				'plugins/osat/assets/css/styles.css' : 'plugins/osat/assets/_less/styles.less'
    			}
            },
            pluginlogin : {
    			options: {
    				compress: true,
    				yuicompress: true,
    				optimization: 2,
                    sourceMap : true,
                    sourceMapFilename : 'plugins/login/assets/css/styles.css.map',
                    sourceMapURL : './styles.css.map',
    				plugins: [
    					new (require('less-plugin-autoprefix'))({browsers: pkg.supportedBrowsers })
    				]
    			},
    			files: {
    				'plugins/login/assets/css/styles.css' : 'plugins/login/assets/_less/styles.less'
    			}
            },
            thirdparty : {
    			options: {
    				compress: true,
    				yuicompress: true,
    				optimization: 2,
                    sourceMap : true,
                    sourceMapFilename : 'thirdparty/css/styles.css.map',
                    sourceMapURL : './styles.css.map',
    				thirdpartys: [
    					new (require('less-plugin-autoprefix'))({browsers: pkg.supportedBrowsers })
    				],
    			},
    			files: {
    				'thirdparty/css/styles.css' : 'thirdparty/_less/styles.less'
    			}
            }
		},

		concat: {
			options: {
				separator: ';\n',
				stripBanners: true,
                sourceMap: true
			},

			template: {
				src: ['template/_js/**/*.js'],
				dest: 'template/scripts/scripts.js'
			},

			plugin: {
				src: ['plugins/osat/assets/_js/**/*.js'],
				dest: 'plugins/osat/assets/js/scripts.js'
			},

			pluginlogin: {
				src: ['plugins/login/assets/_js/**/*.js'],
				dest: 'plugins/login/assets/js/scripts.js'
			},

			thirdparty: {
				src: ['thirdparty/_js/**/*.js'],
				dest: 'thirdparty/js/scripts.js'
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
			},

			template: {
				src: ['<%= concat.template.dest %>'],
				dest: '<%= concat.template.dest %>'
			},

			plugin: {
				src: ['<%= concat.plugin.dest %>'],
				dest: '<%= concat.plugin.dest %>'
	        },

			pluginlogin: {
				src: ['<%= concat.pluginlogin.dest %>'],
				dest: '<%= concat.pluginlogin.dest %>'
	        },

			thirdparty: {
				src: ['<%= concat.thirdparty.dest %>'],
				dest: '<%= concat.thirdparty.dest %>'
	        }
		},

        shell: {
            template : {
                command: 'rsync -rlt --exclude-from "rsync.exclude" --delete-excluded template/ ' + pkg.folders.template + '/ && rsync -rlt --exclude-from "rsync.exclude" --delete-excluded template/ ' + pkg.folders.upload + '/ && rm -rf ' + pkg.folders.temmplatecache
            },
            plugins : {
                command: 'rsync -rlt --exclude-from "rsync.exclude" --delete-excluded plugins/ ' + pkg.folders.plugins + '/'
            },
            thirdparty : {
                command: 'rsync -rlt --exclude-from "rsync.exclude" --delete-excluded thirdparty/ ' + pkg.folders.thirdparty + '/'
            },
            application : {
                command: './lime.sh'
            }
        },

		watch: {
			css_template: {
				files: ['template/_less/**/*.less'], // which files to watch
				tasks: ['less:template', 'shell:template'],
				options: {
				}
			},
			css_plugin: {
				files: ['plugins/osat/assets/_less/**/*.less'], // which files to watch
				tasks: ['less:plugin', 'shell:plugins'],
				options: {
				}
			},
			css_pluginlogin: {
				files: ['plugins/login/assets/_less/**/*.less'], // which files to watch
				tasks: ['less:pluginlogin', 'shell:plugins'],
				options: {
				}
			},
			css_thirdparty: {
				files: ['thirdparty/_less/**/*.less'], // which files to watch
				tasks: ['less:thirdparty', 'shell:thirdparty'],
				options: {
				}
			},
			js_template: {
				files: ['<%= concat.template.src %>'],
				tasks: ['concat:template', 'uglify:template', 'shell:template']
			},
			js_plugin: {
				files: ['<%= concat.plugin.src %>'],
				tasks: ['concat:plugin', 'uglify:plugin', 'shell:plugins']
			},
			js_pluginlogin: {
				files: ['<%= concat.pluginlogin.src %>'],
				tasks: ['concat:pluginlogin', 'uglify:pluginlogin', 'shell:plugins']
			},
			js_thirdparty: {
				files: ['<%= concat.thirdparty.src %>'],
				tasks: ['concat:thirdparty', 'uglify:thirdparty', 'shell:thirdparty']
			},
            rsync_template: {
              files: ['template/**/*', '!template/_*/**/*'],
              tasks: ['shell:template']
            },
            rsync_plugins: {
              files: ['plugins/**/*', '!plugins/_*/**/*'],
              tasks: ['shell:plugins']
            },
            rsync_thirdparty: {
              files: ['thirdparty/**/*', '!thirdparty/_*/**/*'],
              tasks: ['shell:thirdparty']
            },
            rsync_application: {
              files: ['limesurvey_source/**/*', '!limesurvey_source/_*/**/*'],
              tasks: ['shell:application']
            }
		}
    });

    grunt.registerTask('default', ['concat', 'uglify', 'less', 'shell', 'watch']);

};
