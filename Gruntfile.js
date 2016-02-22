module.exports = function(grunt) {
	"use strict";

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
					'box-sizing' : false,
					'font-sizes' : false,
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
					"!common/libraries/**",
					"!vendor/**",
					"!tests/_output/**"
				],
			},
		}
	});
	
	grunt.loadNpmTasks('grunt-contrib-jshint');
	grunt.loadNpmTasks('grunt-contrib-csslint');
	grunt.loadNpmTasks('grunt-phplint');
	
	grunt.registerTask('lint', ['jshint', 'csslint', 'phplint']);
};
