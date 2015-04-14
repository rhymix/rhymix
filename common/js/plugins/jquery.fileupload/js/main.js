(function($){
	"use strict";

	var default_settings = {
		autoUpload: true,
		dataType: 'json',
		replaceFileInput: false,

		dropZone: '.xefu-dropzone',
		fileList: '.xefu-list',
		controll: '.xefu-controll',
		filelist: '.xefu-list-files ul',
		filelistImages: '.xefu-list-images ul',

		progressbar: '.xefu-progressbar',
		progressbarGraph: '.xefu-progressbar div',
		progressStatus: '.xefu-progress-status',
		progressPercent: '.xefu-progress-percent',

		actSelectedInsertContent : '.xefu-act-link-selected',
		actSelectedDeleteFile : '.xefu-act-delete-selected',
		actDeleteFile : '.xefu-act-delete',

		tmplXeUploaderFileitem : '<li class="xefu-file xe-clearfix" data-file-srl="{{file_srl}}"><span class="xefu-file-name">{{source_filename}}</span><span class="xefu-file-info"><span>{{disp_file_size}}</span><span><input type="checkbox" data-file-srl="{{file_srl}}"> 선택</span></span></li>',
		tmplXeUploaderFileitemImage: '<li class="xefu-file xefu-file-image" data-file-srl="{{file_srl}}"><strong class="xefu-file-name">{{source_filename}}</strong><span class="xefu-file-info"><span class="xefu-file-size">{{disp_file_size}}</span><span><img src="{{download_url}}" alt=""></span><span><input type="checkbox" data-file-srl="{{file_srl}}"></span></span></li>'
	};

	var _elements = [
		'fileList',
		'actSelectedInsertContent',
		'actSelectedDeleteFile',
		'actDeleteFile',
		'controll',
		'dropZone',
		'filelist',
		'filelistImages',
		'progressbar',
		'progressbarGraph',
		'progressPercent',
		'progressStatus',
	];

	var XeUploader = xe.createApp('XeUploader', {
		files: {},
		selected_files: {},
		settings: {},
		last_selected_file: null,
		editor_sequence: null,
		init : function() {
		},
		createInstance: function(containerEl, opt) {
			var self = this;
			var $container = this.$container = containerEl;
			var data = $container.data();
			this.editor_sequence = data.editorSequence;

			var settings = {
				url: request_uri.setQuery('module', 'file').setQuery('act', 'procFileUpload'),
				formData: {"editor_sequence": data.editorSequence, "upload_target_srl" : data.uploadTargetSrl, "mid" : window.current_mid},

				dropZone: $container,
				add: function(e, data) {
					var dfd = jQuery.Deferred();

					$.each(data.files, function(index, file) {
						if(self.settings.maxFileSize <= file.size) {
							dfd.reject();
							alert(window.xe.msg_exceeds_limit_size);
							return false;
						}
						dfd.resolve();
					});

					dfd.done(function(){
						data.submit();
					});
				},
				done: function(e, res) {
					var result = res.response().result;

					if(!result) return;

					if(!jQuery.isPlainObject(result)) result = jQuery.parseJSON(result);

					if(!result) return;

					if(result.error == 0) {
					} else {
						alert(result.message);
					}
				},
				stop: function() {
					self.loadFilelist();
				},
				start: function() {
					self.settings.progressbarGraph.width(0);
					self.settings.progressStatus.show();
					self.settings.progressbar.show();
				},
				progressall: function (e, data) {
					var progress = parseInt(data.loaded / data.total * 100, 10);
					self.settings.progressbarGraph.width(progress+'%');
					self.settings.progressPercent.text(progress+'%');

					if(progress >= 100) {
						self.settings.progressbar.delay(3000).slideUp();
						self.settings.progressStatus.delay(3000).slideUp();
					}
				}
			};
			this.settings = $.extend({} , default_settings, settings, opt || {});

			$.each(_elements, function(idx, val) {
				if(typeof self.settings[val] === 'string') self.settings[val] = $container.find(self.settings[val]);
			});

			var INS = $container.fileupload(this.settings)
				.prop('disabled', !$.support.fileInput)
				.parent()
				.addClass($.support.fileInput ? undefined : 'disabled');

			$container.data('xefu-instance', this);

			// 파일 목록 불러오기
			this.loadFilelist();

			// 본문 삽입
			this.settings.actSelectedInsertContent.on('click', function() {
				self.insertToContent();
			});

			// 파일 삭제
			this.settings.actSelectedDeleteFile.on('click', function() {
				self.deleteFile();
			});

			// finderSelect
			var fileselect = this.settings.fileList.finderSelect({children:"li"});
			this.settings.fileList.on("mousedown", 'img', function(e){ e.preventDefault(); });
			fileselect.finderSelect('addHook','highlight:after', function(el) {
				el.find('input').prop('checked', true);
				var selected = self.settings.fileList.find('input:checked');
				self.selected_files = selected;
			});
			fileselect.finderSelect('addHook','unHighlight:after', function(el) {
				el.find('input').prop('checked', false);
				var selected = self.settings.fileList.find('input:checked');
				self.selected_files = selected;
			});
			fileselect.on("click", ":checkbox", function(e){
				e.preventDefault();
			});

			$(document).bind('dragover', function (e) {
				var timeout = window.dropZoneTimeout;
				if (!timeout) {
					self.settings.dropZone.addClass('in');
				} else {
					clearTimeout(timeout);
				}
				var found = false,
				node = e.target;
				do {
					if (node === dropZone[0]) {
						found = true;
						break;
					}
					node = node.parentNode;
				} while (node != null);
				if (found) {
					self.settings.dropZone.addClass('hover');
				} else {
					self.settings.dropZone.removeClass('hover');
				}
				window.dropZoneTimeout = setTimeout(function () {
					window.dropZoneTimeout = null;
					self.settings.dropZone.removeClass('in hover');
				}, 100);
			});
		},
		done: function() {
			// this.loadFilelist();
		},
		selectAllFiles: function() {},
		selectImageFiles: function() {},
		selectNonImageFiles: function() {},
		unselectAllFiles: function() {},
		unselectImageFiles: function() {},
		unselectNonImageFiles: function() {},

		insertToContent: function() {
			var self = this;
			var temp_code = '';

			$.each(this.selected_files, function(idx, file) {
				var file_srl = $(file).data().fileSrl;
				var fileinfo = self.files[file_srl];

				if(!fileinfo) return;

				if(/\.(jpe?g|png|gif)$/i.test(fileinfo.download_url)) {
					temp_code += '<img src="' + window.request_uri + fileinfo.download_url + '" alt="' + fileinfo.source_filename + '" editor_component="image_link" data-file-srl="' + fileinfo.file_srl + '" />';
					temp_code += "\r\n<p><br></p>\r\n";
				} else {
					temp_code += '<a href="' + window.request_uri + fileinfo.download_url + '" data-file-srl="' + fileinfo.file_srl + '">' + fileinfo.source_filename + "</a>\n";
				}

			});

			_getCkeInstance(this.editor_sequence).insertHtml(temp_code, "unfiltered_html");
		},
		/**
		 * 지정된 하나의 파일 또는 다중 선택된 파일 삭제
		 */
		deleteFile: function(file_srl) {
			var self = this;
			var file_srls = [];

			if(!file_srl)
			{
				$.each(self.selected_files, function(idx, file) {
					if(!file) return;

					var file_srl = $(file).data().fileSrl;

					file_srls.push(file_srl);
				});
			}
			else
			{
				file_srls.push(file_srl);
			}

			file_srls = file_srls.join(',');

			exec_json('file.procFileDelete', {'file_srls': file_srls, 'editor_sequence': this.editor_sequence}, function() {
				file_srls = file_srls.split(',');
				$.each(file_srls, function(idx, srl){
					self.settings.fileList.find('ul').find('li[data-file-srl=' + srl + ']').remove();
				});
				self.loadFilelist();
			});
		},
		/**
		 * 파일 목록 갱신
		 */
		loadFilelist: function() {
			var self = this;
			var data = this.$container.data();

			$.exec_json('file.getFileList', {'editor_sequence': self.$container.data('editor-sequence')}, function(res){
				data.uploadTargetSrl = res.upload_target_srl;
				editorRelKeys[self.$container.data('editor-sequence')].primary.value = res.upload_target_srl;
				data.uploadTargetSrl = res.uploadTargetSrl;

				// @TODO 정리
				$('.allowed_filetypes').text(res.allowed_filetypes);
				$('.allowed_filesize').text(res.allowed_filesize);
				$('.allowed_attach_size').text(res.allowed_attach_size);
				$('.attached_size').text(res.attached_size);
				$('.file_count').text(res.files.length);

				var tmpl_fileitem = self.settings.tmplXeUploaderFileitem;
				var tmpl_fileitem_image = self.settings.tmplXeUploaderFileitemImage;
				var template_fileimte = Handlebars.compile(tmpl_fileitem);
				var template_fileimte_image = Handlebars.compile(tmpl_fileitem_image);
				var result_image = [];
				var result = [];

				// 첨부된 파일이 없으면 감춤
				if(!res.files.length) {
					self.settings.fileList.hide();
					self.settings.controll.hide();
					return;
				}

				// 이미지와 그외 파일 분리
				$.each(res.files, function (index, file) {
					if(self.files[file.file_srl]) return;

					self.files[file.file_srl] = file;

					if(/\.(jpe?g|png|gif)$/i.test(file.source_filename)) {
						result_image.push(template_fileimte_image(file));
					}
					else
					{
						result.push(template_fileimte(file));
					}
				});

				// 파일 목록
				self.settings.filelistImages.append(result_image.join(''));
				self.settings.filelist.append(result.join(''));

				// 컨트롤, 리스트 표시
				self.settings.controll.show()
				self.settings.fileList.show();
			});
		}
	});

	// Shortcut function in jQuery
	$.fn.xeUploader = function(opts) {
		var u = new XeUploader();

		if(u) {
			xe.registerApp(u);
			u.createInstance(this.eq(0), opts);
		}

		return u;
	};

	// Shortcut function in XE
	// xe.createXeUploader = function(browseButton, opts) {
	// 	var u = new XeUploader(browseButton, opts);
	// 	if(u) xe.registerApp(u);

	// 	return u;
	// };
})(jQuery);



