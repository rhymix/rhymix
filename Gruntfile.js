module.exports = function(grunt) {
	"use strict";

	grunt.file.defaultEncoding = 'utf8';

	grunt.initConfig({
		jshint: {
			files: [
				'Gruntfile.js',
				'common/js/*.js', '!common/js/html5.js', '!common/js/jquery.js', '!common/js/x.js', '!common/js/xe.js',
				// 'addons/**/*.js',
				// 'modules/**/*.js',
				// 'layouts/**/*.js',
				// 'm.layouts/**/*.js',
				// 'widgets/**/*.js',
				// 'widgetstyles/**/*.js',
				'!**/*.min.js',
				'!**/*-packed.js'
			],
			options : {
				globalstrict: false,
				undef : false,
				eqeqeq: false,
				browser : true,
				globals: {
					"jQuery" : true,
					"console" : true,
					"window" : true
				},
				ignores : [
					'skins/xe_2010_gallery/js/jquery.easing.1.3.js',
					'skins/xe_2010_gallery/js/json2007.js',
					'**/*.min.js',
					'**/*.compressed.js'
				]
			}
		},
		csslint: {
			strict: {
				options: {
					import : 2,
					'adjoining-classes' : false,
					'box-model' : false,
					'duplicate-background-images' : false,
					'ids' : false,
					'important' : false,
					'overqualified-elements' : false,
					'qualified-headings' : false,
					'star-property-hack' : false,
					'underscore-property-hack' : false,
				},
				src: [
					'common/css/*.css',
					'!common/css/bootstrap.css',
					'!common/css/bootstrap-responsive.css',
					'!**/*.min.css',
				]
			}
		},
		cssmin: {
			'common-css': {
				options: {
					banner: '/* My minified css file */'
				},
				files: {
					'path/to/output.css': ['path/to/**/*.css']
				}
			}
		}
	});

	grunt.registerTask('build', '', function(A, B) {
		var _only_export = false;
		var tasks = ['krzip', 'syndication'];

		if(!A) {
			grunt.fail.warn('Undefined build target.');
		} else if(A && !B) {
			_only_export = true;
		}

		if(!_only_export) {
			tasks.push('changed');
			target = A + '...' + B;
			version = B;
		} else {
			target = A;
			version = A;
		}

		var done = this.async();
		var build_dir = 'build';
		var archive_full = build_dir + '/xe.' + version + '.tar.gz';
		var archive_changed = build_dir + '/xe.' + version + '.changed.tar.gz';
		var diff, target, version;

		var taskDone = function() {
			tasks.pop();
			grunt.verbose.writeln('remain tasks : '+tasks.length);

			if(tasks.length === 0) {
				grunt.util.spawn({
					cmd: "tar",
					args: ['cfz', 'xe.'+version+'.tar.gz', 'xe/'],
					opts: {
						cwd: 'build'
					}
				}, function (error, result, code) {
					grunt.log.ok('Archived(full) : ' + archive_full);

					grunt.util.spawn({
						cmd: "zip",
						args: ['-r', 'xe.'+version+'.zip', 'xe/'],
						opts: {
							cwd: 'build'
						}
					}, function (error, result, code) {
						grunt.log.ok('Archived(full) : ' + archive_full);

						grunt.file.delete('build/xe');
						grunt.file.delete('build/temp.full.tar');

						grunt.log.ok('Done!');
					});
				});
			}
		};

		if(grunt.file.isDir(build_dir)) {
			grunt.file.delete(build_dir);
		}
		grunt.file.mkdir(build_dir);
		grunt.file.mkdir(build_dir + '/xe');

		grunt.log.subhead('Archiving...');
		grunt.log.writeln('Target : ' + target);

		grunt.util.spawn({
			cmd: "git",
			args: ['archive', '--output=build/temp.full.tar', version, '.']
		}, function (error, result, code){
			if(!_only_export) {
				// changed
				grunt.util.spawn({
					cmd: "git",
					args: ['diff', '--name-only', target]
				}, function (error, result, code) {
					diff = result.stdout;

					if(diff) {
						diff = diff.split(grunt.util.linefeed);
					}

					// changed
					if(diff.length) {
						var args_tar = ['archive', '--prefix=xe/', '-o', 'build/xe.'+version+'.changed.tar.gz', version];
						var args_zip = ['archive', '--prefix=xe/', '-o', 'build/xe.'+version+'.changed.zip', version];
						args_tar = args_tar.concat(diff);
						args_zip = args_zip.concat(diff);

						grunt.util.spawn({
							cmd: "git",
							args: args_tar
						}, function (error, result, code) {
							grunt.util.spawn({
								cmd: "git",
								args: args_zip
							}, function (error, result, code) {
								grunt.log.ok('Archived(changed) : ./build/xe.'+version+'.changed.tar.gz');
								taskDone();
							});
						});
					} else {
						taskDone();
					}
				});
			}

			// full
			grunt.util.spawn({
				cmd: "tar",
				args: ['xf', 'build/temp.full.tar', '-C', 'build/xe']
			}, function (error, result, code) {
				// krzip
				grunt.util.spawn({
					cmd: "git",
					args: ['clone', 'git@github.com:xpressengine/xe-module-krzip.git', 'build/xe/modules/krzip']
				}, function (error, result, code) {
					grunt.file.delete('build/xe/modules/krzip/.git');
					taskDone();
				});

				// syndication
				grunt.util.spawn({
					cmd: "git",
					args: ['clone', 'git@github.com:xpressengine/xe-module-syndication.git', 'build/xe/modules/syndication']
				}, function (error, result, code) {
					grunt.file.delete('build/xe/modules/syndication/.git');
					taskDone();
				});
			});
		});
	});

	grunt.loadNpmTasks('grunt-contrib-jshint');
	grunt.loadNpmTasks('grunt-contrib-csslint');
	grunt.loadNpmTasks('grunt-contrib-cssmin');

	grunt.registerTask('default', ['jshint', 'csslint']);
};
