module.exports = function(grunt) {
	"use strict";

	var banner = '/*! Copyright (C) NAVER <http://www.navercorp.com> */\n';
	var banner_xe_js = banner + '/**!\n * @concat modernizr.js + common.js + js_app.js + xml_handler.js + xml_js_filter.js\n * @brief XE Common JavaScript\n **/\n';

	grunt.file.defaultEncoding = 'utf8';

	grunt.initConfig({
		jshint: {
			files: [
				'Gruntfile.js',
				'common/js/*.js',
				'modules/admin/tpl/js/*.js',
				'modules/board/tpl/js/*.js',
				'modules/board/skins/*/*.js',
				'modules/editor/tpl/js/*.js',
				'modules/menu/tpl/js/*.js',
				'modules/widget/tpl/js/*.js',
			],
			options : {
				ignores : [
					'**/jquery*.js',
					'**/swfupload.js',
					'**/**.min.js',
					'**/*-packed.js',
					'**/*.compressed.js',
					'**/jquery-*.js',
					'**/jquery.*.js',
					'common/js/html5.js',
					'common/js/x.js',
					'common/js/xe.js',
					'common/js/modernizr.js',
					'vendor/**',
					'tests/**',
				]
			}
		},
		csslint: {
			'common-css': {
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
					'!vendor/**',
					'!tests/**',
				]
			}
		},
		phplint: {
			default : {
				options: {
					phpCmd: "php",
				},

				src: [
					"**/*.php",
					"!files/**",
					"!tests/**",
					"!tools/**",
					"!node_modules/**",
					"!libs/**",
					"!vendor/**",
					"!tests/_output/**"
				],
			},
		}
	});

	function createPackageChecksum(target_file) {
		/* https://gist.github.com/onelaview/6475037 */
		var fs = require('fs');
		var crypto = require('crypto');
		var md5 = crypto.createHash('md5');
		var file = grunt.template.process(target_file);
		var buffer = fs.readFileSync(file);
		md5.update(buffer);
		var md5Hash = md5.digest('hex');
		grunt.verbose.writeln('file md5: ' + md5Hash);

		var md5FileName = file + '.md5';
		grunt.file.write(md5FileName, md5Hash);
		grunt.verbose.writeln('File "' + md5FileName + '" created.').writeln('...');
	}

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
					args: ['cfz', '../xe.'+version+'.tar.gz', './'],
					opts: {
						cwd: 'build/xe',
						cache: false
					}
				}, function (error, result, code) {
					grunt.log.ok('Archived(full) : ' + build_dir + '/xe.'+version+'.tar.gz');
					createPackageChecksum(build_dir + '/xe.'+version+'.tar.gz');

					grunt.util.spawn({
						cmd: "zip",
						args: ['-r', '../xe.'+version+'.zip', './'],
						opts: {
							cwd: 'build/xe',
							cache: false
						}
					}, function (error, result, code) {
						grunt.log.ok('Archived(full) : ' + build_dir + '/xe.'+version+'.zip');
						createPackageChecksum(build_dir + '/xe.'+version+'.zip');

						grunt.file.delete('build/xe');
						grunt.file.delete('build/temp.full.tar');

						grunt.util.spawn({
							cmd: "git",
							args: ['diff', '--name-status', target]
						}, function (error, result, code) {
							var fs = require('fs');
							result = 'Added (A), Copied (C), Deleted (D), Modified (M), Renamed (R).' + grunt.util.linefeed + result;
							grunt.file.write(build_dir + '/CHANGED.' + version + '.txt', result);

							grunt.log.ok('Done!');
						});

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
					args: ['diff', '--name-only', '--diff-filter' ,'ACM', target]
				}, function (error, result, code) {
					diff = result.stdout;

					if(diff) {
						diff = diff.split(grunt.util.linefeed);
					}

					// changed
					if(diff.length) {
						var args_tar = ['archive', '-o', 'build/xe.'+version+'.changed.tar.gz', version];
						var args_zip = ['archive', '-o', 'build/xe.'+version+'.changed.zip', version];
						args_tar = args_tar.concat(diff);
						args_zip = args_zip.concat(diff);

						grunt.util.spawn({
							cmd: "git",
							args: args_tar
						}, function (error, result, code) {
							grunt.log.ok('Archived(changed) : ' + build_dir + '/xe.'+version+'.changed.tar.gz');
							createPackageChecksum(build_dir + '/xe.'+version+'.changed.tar.gz');

							grunt.util.spawn({
								cmd: "git",
								args: args_zip
							}, function (error, result, code) {
								grunt.log.ok('Archived(changed) : ' + build_dir + '/xe.'+version+'.changed.zip');
								createPackageChecksum(build_dir + '/xe.'+version+'.changed.zip');

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
					args: ['clone', '-b', 'master', 'git@github.com:xpressengine/xe-module-krzip.git', 'build/xe/modules/krzip']
				}, function (error, result, code) {
					grunt.file.delete('build/xe/modules/krzip/.git');
					taskDone();
				});

				// syndication
				grunt.util.spawn({
					cmd: "git",
					args: ['clone', '-b', 'master', 'git@github.com:xpressengine/xe-module-syndication.git', 'build/xe/modules/syndication']
				}, function (error, result, code) {
					grunt.file.delete('build/xe/modules/syndication/.git');
					taskDone();
				});
			});
		});
	});

	grunt.loadNpmTasks('grunt-contrib-clean');
	grunt.loadNpmTasks('grunt-contrib-concat');
	grunt.loadNpmTasks('grunt-contrib-jshint');
	grunt.loadNpmTasks('grunt-contrib-uglify');
	grunt.loadNpmTasks('grunt-contrib-csslint');
	grunt.loadNpmTasks('grunt-contrib-cssmin');
	grunt.loadNpmTasks('grunt-phplint');

	grunt.registerTask('default', ['jshint', 'csslint']);
	grunt.registerTask('lint', ['jshint', 'csslint', 'phplint']);
	grunt.registerTask('minify', ['jshint', 'csslint', 'clean', 'concat', 'uglify', 'cssmin']);
};
