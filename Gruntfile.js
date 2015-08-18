module.exports = function(grunt) {
	"use strict";

	var banner = '/*! Copyright (C) NAVER <http://www.navercorp.com> */\n';
	var banner_xe_js = banner + '/**!\n * @concat modernizr.js + common.js + js_app.js + xml_handler.js + xml_js_filter.js\n * @brief XE Common JavaScript\n **/\n';

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
					'common/js/modernizr.js',
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
					banner: '/**!\n * @concat Xpress_Editor.js + xe_interface.js \n **/\n'
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
					'common/js/xe.min.js': ['common/js/xe.js'],
				}
			},
			'common-js-plugins': {
				files: {
					'common/js/plugins/jquery.fileupload/js/main.min.js': ['common/js/plugins/jquery.fileupload/js/main.js'],
				}
			},
			'handlebars': {
				files: {
					'common/js/plugins/handlebars/handlebars.min.js': ['common/js/plugins/handlebars/handlebars.js'],
					'common/js/plugins/handlebars.runtime/handlebars.runtime.min.js': ['common/js/plugins/handlebars.runtime/handlebars.runtime.js'],
				}
			},
			'modules': {
				files: {
					'common/js/x.min.js' : ['common/js/x.js'],
					// addon
					'addons/captcha/captcha.min.js' : ['addons/captcha/captcha.js'],
					'addons/captcha_member/captcha.min.js' : ['addons/captcha_member/captcha.js'],
					'addons/resize_image/js/resize_image.min.js' : ['addons/resize_image/js/resize_image.js'],
					// module/editor
					'modules/editor/skins/xpresseditor/js/xpresseditor.min.js': ['modules/editor/skins/xpresseditor/js/xpresseditor.js'],
					'modules/editor/skins/xpresseditor/js/xe_textarea.min.js': ['modules/editor/skins/xpresseditor/js/xe_textarea.js'],
					'modules/editor/skins/ckeditor/js/default.min.js': ['modules/editor/skins/ckeditor/js/default.js'],
					'modules/editor/skins/ckeditor/js/xe_interface.min.js': ['modules/editor/skins/ckeditor/js/xe_interface.js'],
					'modules/editor/skins/ckeditor/js/xe_textarea.min.js': ['modules/editor/skins/ckeditor/js/xe_textarea.js'],
					'modules/editor/tpl/js/editor_common.min.js': ['modules/editor/tpl/js/editor_common.js'],
					'modules/editor/tpl/js/swfupload.min.js': ['modules/editor/tpl/js/swfupload.js'],
					'modules/editor/tpl/js/uploader.min.js': ['modules/editor/tpl/js/uploader.js'],
					'modules/editor/tpl/js/editor.min.js': ['modules/editor/tpl/js/editor.js'],
					'modules/editor/tpl/js/editor_module_config.min.js': ['modules/editor/tpl/js/editor_module_config.js'],
					'modules/editor/tpl/js/editor.app.min.js': ['modules/editor/tpl/js/editor.app.js'],
					// module/admin
					'modules/admin/tpl/js/admin.min.js': ['modules/admin/tpl/js/admin.js'],
					'modules/admin/tpl/js/config.min.js': ['modules/admin/tpl/js/config.js'],
					'modules/admin/tpl/js/menu_setup.min.js': ['modules/admin/tpl/js/menu_setup.js'],
					//module/board
					'modules/board/tpl/js/board.min.js': ['modules/board/tpl/js/board.js'],
					'modules/board/tpl/js/board_admin.min.js': ['modules/board/tpl/js/board_admin.js'],
					'modules/board/skins/default/board.default.min.js': ['modules/board/skins/default/board.default.js'],
					'modules/board/m.skins/default/js/mboard.min.js': ['modules/board/m.skins/default/js/mboard.js'],
					'modules/board/m.skins/simpleGray/js/mboard.min.js': ['modules/board/m.skins/simpleGray/js/mboard.js'],
					'modules/board/skins/xedition/board.default.min.js': ['modules/board/skins/xedition/board.default.js'],
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
			},
			'layout': {
				files: {
					'layouts/xedition/js/layout.min.js': ['layouts/xedition/js/layout.js'],
					'layouts/xedition/js/welcome.min.js': ['layouts/xedition/js/welcome.js'],
				}
			},
		},
		cssmin: {
			'common': {
				files: {
					'common/css/xe.min.css': ['common/css/xe.css'],
					'common/css/mobile.min.css': ['common/css/mobile.css']
				}
			},
			'modules': {
				files: {
					'modules/admin/tpl/css/admin.min.css': ['modules/admin/tpl/css/admin.css'],
					'modules/editor/components/image_gallery/tpl/popup.min.css': ['modules/editor/components/image_gallery/tpl/popup.css'],
					'modules/editor/components/image_gallery/tpl/slide_gallery.min.css': ['modules/editor/components/image_gallery/tpl/slide_gallery.css'],
					'modules/widget/tpl/css/widget.min.css': ['modules/widget/tpl/css/widget.css'],
					'modules/poll/tpl/css/poll.min.css': ['modules/poll/tpl/css/poll.css'],
					'modules/poll/skins/default/css/poll.min.css': ['modules/poll/skins/default/css/poll.css'],
					'modules/poll/skins/simple/css/poll.min.css': ['modules/poll/skins/simple/css/poll.css'],
					'modules/editor/skins/xpresseditor/css/default.min.css': ['modules/editor/skins/xpresseditor/css/default.css'],
					'modules/board/skins/default/board.default.min.css': ['modules/board/skins/default/board.default.css'],
					'modules/board/m.skins/default/css/mboard.min.css': ['modules/board/m.skins/default/css/mboard.css'],
					'modules/board/m.skins/simpleGray/css/mboard.min.css': ['modules/board/m.skins/simpleGray/css/mboard.css'],
					'modules/board/skins/xedition/board.default.min.css': ['modules/board/skins/xedition/board.default.css'],
				}
			},
			'addons': {
				files: {
					'addons/oembed/jquery.oembed.min.css': ['addons/oembed/jquery.oembed.css'],
				}
			},
			'layout': {
				files: {
					'layouts/xedition/css/layout.min.css': ['layouts/xedition/css/layout.css'],
					'layouts/xedition/css/webfont.min.css': ['layouts/xedition/css/webfont.css'],
					'layouts/xedition/css/welcome.min.css': ['layouts/xedition/css/welcome.css'],
					'layouts/xedition/css/widget.login.min.css': ['layouts/xedition/css/widget.login.css'],
					'layouts/xedition/css/xeicon.min.css': ['layouts/xedition/css/xeicon.css'],
				}
			},
		},
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
