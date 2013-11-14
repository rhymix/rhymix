module.exports = function(grunt) {
	"use strict";

	grunt.file.defaultEncoding = 'utf8';

	grunt.initConfig({
	});

	grunt.registerTask('build', '', function(A, B) {
		if(!A || !B) grunt.fail.warn('Undefined build target.');

		var done = this.async();
		var build_dir = 'build';
		var archive_full = build_dir + '/xe.' + B + '.tar.gz';
		var archive_changed = build_dir + '/xe.' + B + '.changed.tar.gz';
		var diff;
		var tasks = ['changed', 'krzip', 'syndication'];
		var taskDone = function() {
			tasks.pop();
			grunt.verbose.writeln('remain tasks : '+tasks.length);

			if(tasks.length === 0) {
				grunt.util.spawn({
					cmd: "tar",
					args: ['cfz', 'xe.'+B+'.tar.gz', 'xe/'],
					opts: {
						cwd: 'build'
					}
				}, function (error, result, code) {
					grunt.log.ok('Archived(full) : ' + archive_full);

					grunt.file.delete('build/xe');
					grunt.file.delete('build/temp.full.tar');

					grunt.log.ok('Done!');
				});
			}
		};

		if(grunt.file.isDir(build_dir)) {
			grunt.file.delete(build_dir);
		}
		grunt.file.mkdir(build_dir);
		grunt.file.mkdir(build_dir + '/xe');

		grunt.log.subhead('Archiving...');
		grunt.log.writeln('Target : ' + A + '...' + B);

		grunt.util.spawn({
			cmd: "git",
			args: ['diff', '--name-only', A + '...' + B]
		}, function (error, result, code) {
			diff = result.stdout;

			if(diff) {
				diff = diff.split(grunt.util.linefeed);
			}

			grunt.util.spawn({
				cmd: "git",
				args: ['archive', '--output=build/temp.full.tar', B, '.']
			}, function (error, result, code) {

				grunt.util.spawn({
					cmd: "tar",
					args: ['xf', 'build/temp.full.tar', '-C'
					, 'build/xe'],
				}, function (error, result, code) {

					// krzip
					grunt.util.spawn({
						cmd: "git",
						args: ['clone', 'git@github.com:xpressengine/module-krzip.git', 'build/xe/modules/module-krzip']
					}, function (error, result, code) {
						grunt.file.delete('build/xe/modules/module-krzip/.git');
						taskDone();
					});

					// syndication
					grunt.util.spawn({
						cmd: "git",
						args: ['clone', 'git@github.com:xpressengine/module-syndication.git', 'build/xe/modules/syndication']
					}, function (error, result, code) {
						grunt.file.delete('build/xe/modules/syndication/.git');
						taskDone();
					});
				});

				// changed
				if(diff.length) {
					var args = ['archive', '--prefix=xe/', '-o', 'build/xe.'+B+'.changed.tar.gz', B];
					args = args.concat(diff);
					grunt.util.spawn({
						cmd: "git",
						args: args
					}, function (error, result, code) {
						grunt.log.ok('Archived(changed) : ./build/xe.'+B+'.changed.tar.gz');
						taskDone();
					});
				} else { 
					taskDone();
				}
			});
		});
	});

	grunt.registerTask('default', ['build']);
};
