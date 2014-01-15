module.exports = function(grunt) {
	"use strict";

	var banner = '/*! Copyright (C) NAVER <http://www.navercorp.com> */\n';
	var banner_xe_js = banner + '/**!\n * @file   common.js + js_app.js + xml_handler.js + xml_js_filter.js\n * @brief  XE Common JavaScript\n **/\n';

	grunt.file.defaultEncoding = 'utf8';

	grunt.initConfig({
		clean: {
			minify: [
				'common/js/xe.js',
				'common/js/xe.min.js',
				'common/css/xe.min.css',
				'common/css/mobile.min.css'
			]
		},
		concat: {
			'common-js': {
				options: {
					stripBanners: true,
					banner: banner_xe_js
				},
				src: [
					'common/js/common.js',
					'common/js/js_app.js',
					'common/js/xml_handler.js',
					'common/js/xml_js_filter.js'
				],
				dest: 'common/js/xe.js'
			},
			'xpresseditor': {
				options: {
					stripBanners: true,
					banner: banner_xe_js
				},
				src: [
					'modules/editor/skins/xpresseditor/js/Xpress_Editor.js',
					'modules/editor/skins/xpresseditor/js/xe_interface.js',
				],
				dest: 'modules/editor/skins/xpresseditor/js/xpresseditor.js'
			}
		},
		uglify: {
			'common-js': {
				options: {
					banner: banner_xe_js
				},
				files: {
					'common/js/xe.min.js': ['common/js/xe.js']
				}
			},
			'modules': {
				files: {
					// addon
					'addons/captcha/captcha.min.js' : ['addons/captcha/captcha.js'],
					'addons/captcha_member/captcha.min.js' : ['addons/captcha_member/captcha.js'],
					'addons/resize_image/js/resize_image.min.js' : ['addons/resize_image/js/resize_image.js'],
					// module/editor
					'modules/editor/skins/xpresseditor/js/xpresseditor.min.js': ['modules/editor/skins/xpresseditor/js/xpresseditor.js'],
					'modules/editor/skins/xpresseditor/js/xe_textarea.min.js': ['modules/editor/skins/xpresseditor/js/xe_textarea.js'],
					'modules/editor/tpl/js/editor_common.min.js': ['modules/editor/tpl/js/editor_common.js'],
					// module/admin
					'modules/admin/tpl/js/admin.min.js': ['modules/admin/tpl/js/admin.js'],
					'modules/admin/tpl/js/config.min.js': ['modules/admin/tpl/js/config.js'],
					'modules/admin/tpl/js/menu_setup.min.js': ['modules/admin/tpl/js/menu_setup.js'],
					'modules/admin/tpl/js/sitemap.min.js': ['modules/admin/tpl/js/sitemap.js'],
					'modules/admin/tpl/js/theme.min.js': ['modules/admin/tpl/js/theme.js'],
					// editor-component-multimedia-link
					'modules/editor/components/multimedia_link/tpl/popup.min.js': ['modules/editor/components/multimedia_link/tpl/popup.js'],
					// editor-component-image-gallery
					'modules/editor/components/image_gallery/tpl/gallery.min.js' : ['modules/editor/components/image_gallery/tpl/gallery.js'],
					'modules/editor/components/image_gallery/tpl/list_gallery.min.js' : ['modules/editor/components/image_gallery/tpl/list_gallery.js'],
					'modules/editor/components/image_gallery/tpl/popup.min.js' : ['modules/editor/components/image_gallery/tpl/popup.js'],
					'modules/editor/components/image_gallery/tpl/slide_gallery.min.js' : ['modules/editor/components/image_gallery/tpl/slide_gallery.js'],
					// module/importer
					'modules/importer/tpl/js/importer_admin.min.js': ['modules/importer/tpl/js/importer_admin.js'],
					// modules/widget
					'modules/widget/tpl/js/generate_code.min.js': ['modules/widget/tpl/js/generate_code.js'],
					'modules/widget/tpl/js/widget.min.js': ['modules/widget/tpl/js/widget.js'],
					'modules/widget/tpl/js/widget_admin.min.js': ['modules/widget/tpl/js/widget_admin.js'],
					// modules/poll
					'modules/poll/tpl/js/poll_admin.min.js': ['modules/poll/tpl/js/poll_admin.js'],
					'modules/poll/tpl/js/poll.min.js': ['modules/poll/tpl/js/poll.js'],
					'addons/oembed/jquery.oembed.min.js': ['addons/oembed/jquery.oembed.js'],
					'addons/oembed/oembed.min.js': ['addons/oembed/oembed.js'],
				}
			}
		},
		cssmin: {
			'common-css': {
				files: {
					'common/css/xe.min.css': ['common/css/xe.css'],
					'common/css/mobile.min.css': ['common/css/mobile.css']
				}
			},
			'module-admin-css': {
				files: {
					'modules/admin/tpl/css/admin.min.css': ['modules/admin/tpl/css/admin.css']
				}
			},
			'editor-component-image-gallery': {
				files: {
					'modules/editor/components/image_gallery/tpl/popup.min.css': ['modules/editor/components/image_gallery/tpl/popup.css'],
					'modules/editor/components/image_gallery/tpl/slide_gallery.min.css': ['modules/editor/components/image_gallery/tpl/slide_gallery.css'],
				}
			},
			'editor-component-multimedia-link': {
				files: {
					'modules/editor/components/multimedia_link/tpl/popup.min.css': ['modules/editor/components/multimedia_link/tpl/popup.css'],
				}
			},
			'moudle-widget-tpl': {
				files: {
					'modules/widget/tpl/css/widget.min.css': ['modules/widget/tpl/css/widget.css'],
				}
			},
			'moudle-poll': {
				files: {
					'modules/poll/tpl/css/poll.min.css': ['modules/poll/tpl/css/poll.css'],
					'modules/poll/skins/default/css/poll.min.css': ['modules/poll/skins/default/css/poll.css'],
					'modules/poll/skins/simple/css/poll.min.css': ['modules/poll/skins/simple/css/poll.css'],
				}
			},
			'moudle-editor': {
				files: {
					'modules/editor/skins/xpresseditor/css/default.min.css': ['modules/editor/skins/xpresseditor/css/default.css'],
				}
			},
			'addon-oembed': {
				files: {
					'addons/oembed/jquery.oembed.min.css': ['addons/oembed/jquery.oembed.css'],
				}
			}
		},
		jshint: {
			files: [
				'Gruntfile.js',
				'common/js/*.js', '!common/js/html5.js', '!common/js/jquery.js', '!common/js/x.js', '!common/js/xe.js',
				'modules/widget/tpl/js/generate_code.js',
				'modules/widget/tpl/js/widget.js',
				'modules/widget/tpl/js/widget_admin.js',
				'!**/jquery*.js',
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
					"!libs/**"
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
		var tasks = ['board', 'krzip', 'syndication'];

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
					grunt.log.ok('Archived(full) : ' + build_dir + '/xe.'+version+'.tar.gz');
					createPackageChecksum(build_dir + '/xe.'+version+'.tar.gz');

					grunt.util.spawn({
						cmd: "zip",
						args: ['-r', 'xe.'+version+'.zip', 'xe/'],
						opts: {
							cwd: 'build'
						}
					}, function (error, result, code) {
						grunt.log.ok('Archived(full) : ' + build_dir + '/xe.'+version+'.zip');
						createPackageChecksum(build_dir + '/xe.'+version+'.zip');

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
				// board
				grunt.util.spawn({
					cmd: "git",
					args: ['clone', '-b', 'master', 'git@github.com:xpressengine/xe-module-board.git', 'build/xe/modules/board']
				}, function (error, result, code) {
					grunt.file.delete('build/xe/modules/board/.git');
					taskDone();
				});

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
