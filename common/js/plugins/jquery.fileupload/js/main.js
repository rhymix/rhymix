(function($){
	"use strict";

	var XeUploader = xe.createApp('XeUploader', {
		files: {},
		selected_files: [],
		last_selected_file: null,
		editor_sequence: null,
		init : function() {
			this.file_list_container = $('.xe-uploader-filelist select');
		},
		createInstance: function(containerEl, opt) {
			var self = this;
			var $container = this.$container = containerEl;
			var data = $container.data();
			this.editor_sequence = data.editorSequence;

			var settings = {
				url: request_uri.setQuery('module', 'file').setQuery('act', 'procFileUpload'),
				autoUpload: true,
				formData: {"editor_sequence": data.editorSequence, "upload_target_srl" : data.uploadTargetSrl},
				dataType: 'json',
				dropZone: $container,
				done: function(e, res) {
					var result = res.result;

					if(!result) return;

					if(result.error == 0) {
						self.done.call(self, arguments);
					} else {
						alert(result.message);
					}
				},
				stop: function() {
					self.loadFilelist();
				},
				start: function() {
					$('#progress').find('.progress-bar').width(0).addBack().show();
				},
				progressall: function (e, data) {
					var progress = parseInt(data.loaded / data.total * 100, 10);
					$('.progress-bar').width(progress+'%');

					if(progress >= 100) {
						$('#progress').delay(5000).slideUp();
					}
				}
			};
			$.extend(settings, opt || {});

			var INS = $container.fileupload(settings)
				.prop('disabled', !$.support.fileInput)
				.parent()
				.addClass($.support.fileInput ? undefined : 'disabled');

			$container.data('xe-uploader-instance', this);

			// 파일 선택
			$(this.file_list_container).on('change', function(e) {
				var $el = self.file_list_container.find('option:selected');

				self.selected_files = [];
				$el.each(function(idx, el) {
					self.selected_files.push(el);
				});
			});

			// 파일 선택
			$(this.file_list_container).on('click', function(e) {
				if(e.target.tagName === 'OPTION')
				{
					self.last_selected_file = e.target;
					self.selectFile();
				}
			});

			this.loadFilelist();

			// 본문 삽입
			$('.xe-act-link-selected').on('click', function() {
				self.insertToContent();
			});

			// 파일 삭제
			$('.xe-act-delete-selected').on('click', function() {
				self.deleteFile();
			});
		},
		done: function() {
			// this.loadFilelist();
		},
		insertToContent: function() {
			var temp_code = '';

			for(var i = 0, len = this.selected_files.length; i < len; i++) {
				var fileinfo = $(this.selected_files[i]).data('fileinfo');

				if(!fileinfo) return;

				if(/\.(jpe?g|png|gif)$/i.test(fileinfo.download_url)) {
					temp_code += '<img src="' + window.request_uri + fileinfo.download_url + '" alt="' + fileinfo.source_filename + '" editor_component="image_link"  />';
					temp_code += "\r\n<p><br></p>\r\n";
				} else {
					temp_code += '<a href="' + window.request_uri + fileinfo.download_url + '">' + fileinfo.source_filename + "</a>\n";
				}

				_getCkeInstance(this.editor_sequence).insertHtml(temp_code, "unfiltered_html");
			}
		},
		deleteFile: function() {
			var self = this;
			var file_srls = [];

			for(var i = 0, len = this.selected_files.length; i < len; i++) {
				var fileinfo = $(this.selected_files[i]).data('fileinfo');
				file_srls.push(fileinfo.file_srl);
			}

			file_srls = file_srls.join(',');
			exec_json('file.procFileDelete', {'file_srls': file_srls, 'editor_sequence': this.editor_sequence}, function() {
				self.loadFilelist();
			});
		},
		loadFilelist: function() {
			var self = this;
			var data = this.$container.data();

			$.exec_json('file.getFileList', {'editor_sequence': self.$container.data('editor-sequence')}, function(res){
				data.uploadTargetSrl = res.upload_target_srl;
				editorRelKeys[self.$container.data('editor-sequence')].primary.value = res.upload_target_srl;
				self.files = res.files;

				data.uploadTargetSrl = res.uploadTargetSrl;
				$('.xe-uploader-filelist select').empty();
				$('.file_attach_info').html(res.upload_status);
				$.each(res.files, function (index, file) {
					$('<option title="'+file.source_filename+' ('+file.disp_file_size+')" />')
						.data('fileinfo', file)
						.text(file.source_filename+' ('+file.disp_file_size+')')
						.val(file.file_srl)
						.appendTo('.xe-uploader-filelist select');
				});
				self.displayPreview($('.xe-uploader-filelist select option:last').data('fileinfo'));
			});
		},
		selectFile: function() {
			this.displayPreview($(this.last_selected_file).data('fileinfo'));
		},
		displayPreview: function(fileinfo) {
			if(!fileinfo) return;

			if(/\.(jpe?g|png|gif)$/i.test(fileinfo.download_url)) {
				$('.xe-uploader-preview img').attr('src', window.request_uri + fileinfo.download_url).show();
			} else {
				$('.xe-uploader-preview img').hide();
			}
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
