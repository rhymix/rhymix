(function($){
	"use strict";

	var default_settings = {
		autoUpload: true,
		dataType: 'json',
		replaceFileInput: false,

		fileListContaner: $('.xe-uploader-filelist-container'),
		fileItem: 'li',

		actSelectedInsertContent : $('.xe-uploader-act-link-selected'),
		actSelectedDeleteFile : $('.xe-uploader-act-delete-selected'),
		actDeleteFile : $('.xe-uploader-act-delete'),

		tmplXeUploaderFileitem : '<li class="xe-uploader-fileitem xe-uploader-fileitem-file clearfix" data-file-srl="{{file_srl}}"><span class="xe-uploader-fileitem-filename">{{source_filename}}</span><span class="xe-uploader-fileitem-info"><span>{{disp_file_size}}</span><span><input type="checkbox" data-file-srl="{{file_srl}}"> 선택</span></span></li>',
		tmplXeUploaderFileitemImage: '<li class="xe-uploader-fileitem xe-uploader-fileitem-image" data-file-srl="{{file_srl}}"><strong class="xe-uploader-fileitem-filename">{{source_filename}}</strong><span class="xe-uploader-fileitem-info"><span class="xe-uploader-fileitem-info-filesize">{{disp_file_size}}</span><span><img src="{{download_url}}" alt=""></span><span><input type="checkbox" data-file-srl="{{file_srl}}"></span></span></li>'
	};

	var XeUploader = xe.createApp('XeUploader', {
		files: {},
		selected_files: {},
		settings: {},
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
				formData: {"editor_sequence": data.editorSequence, "upload_target_srl" : data.uploadTargetSrl},

				dropZone: $container,
				done: function(e, res) {
					var result = res.response().result;

					if(!result) return;

					console.log(result);
					if(!jQuery.isPlainObject(result)) result = jQuery.parseJSON(result);

					if(!result) return;

					if(result.error == 0) {
						// self.done.call(self, arguments);
					} else {
						alert(result.message);
					}
				},
				stop: function() {
					self.loadFilelist();
				},
				drop: function(e, data) {
				},
				change: function(e, data) {
				},
				always: function() {
					// console.info('@always');
					// console.log(arguments);
				},
				start: function() {
					// console.info('@start');
					// console.log(arguments);
					$('.xe-uploader-progressbar div').width(0);
					$('.xe-uploader-progress-message').show();
					$('.xe-uploader-progressbar').show();
				},
				progressall: function (e, data) {
					var progress = parseInt(data.loaded / data.total * 100, 10);
					$('.xe-uploader-progressbar div').width(progress+'%');
					$('.xe-uploader-progressall').text(progress+'%');

					if(progress >= 100) {
						$('.xe-uploader-progressbar, .xe-uploader-progress-message').delay(3000).slideUp();
					}
				}
			};
			this.settings = $.extend({} , default_settings, settings, opt || {});

			var INS = $container.fileupload(this.settings)
				.prop('disabled', !$.support.fileInput)
				.parent()
				.addClass($.support.fileInput ? undefined : 'disabled');

				console.log('fileupload');
				console.log(INS);
			$container.data('xe-uploader-instance', this);

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
			var fileselect = this.settings.fileListContaner.finderSelect({children:"li"});
			this.settings.fileListContaner.on("mousedown", 'img', function(e){ e.preventDefault(); });
			fileselect.finderSelect('addHook','highlight:after', function(el) {
				el.find('input').prop('checked', true);
				var selected = self.settings.fileListContaner.find('input:checked');
				self.selected_files = selected;
			});
			fileselect.finderSelect('addHook','unHighlight:after', function(el) {
				el.find('input').prop('checked', false);
				var selected = self.settings.fileListContaner.find('input:checked');
				self.selected_files = selected;
			});
			fileselect.on("click", ":checkbox", function(e){
	            e.preventDefault();
	        });



			$(document).bind('dragover', function (e) {
				var dropZone = $('.xe-uploader-container .xe-uploader-dropzone'),
				timeout = window.dropZoneTimeout;
				if (!timeout) {
					dropZone.addClass('in');
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
					dropZone.addClass('hover');
				} else {
					dropZone.removeClass('hover');
				}
				window.dropZoneTimeout = setTimeout(function () {
					window.dropZoneTimeout = null;
					dropZone.removeClass('in hover');
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
			// console.log(this.selected_files);

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
		deleteFile: function(file_srl) {
			var self = this;
			var file_srls = [];

			if(!file_srl)
			{
				$.each(self.selected_files, function(idx, file) {
					if(!file) return;

					var file_srl = $(file).data().fileSrl;
					var fileinfo = self.files[file_srl];

					file_srls.push(fileinfo.file_srl);
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
					$('.xe-uploader-filelist-container ul').find('li[data-file-srl=' + srl + ']').remove();
				});
				self.loadFilelist();
			});
		},
		loadFilelist: function() {
			var self = this;
			var data = this.$container.data();

			$.exec_json('file.getFileList', {'editor_sequence': self.$container.data('editor-sequence')}, function(res){
				data.uploadTargetSrl = res.upload_target_srl;
				editorRelKeys[self.$container.data('editor-sequence')].primary.value = res.upload_target_srl;

				data.uploadTargetSrl = res.uploadTargetSrl;
				$('.xe-uploader-filelist select').empty();
				$('.file_attach_info').html(res.upload_status);

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

				if(!res.files.length) {
					$('.xe-uploader-controll-container, .xe-uploader-filelist-container').hide();
					return;
				}


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

				$('.xe-uploader-filelist-container .xe-uploader-filelist-images ul').append(result_image.join(''));
				$('.xe-uploader-filelist-container .xe-uploader-filelist-files ul').append(result.join(''));

				$('.xe-uploader-controll-container').show()
				self.settings.fileListContaner.show();
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



