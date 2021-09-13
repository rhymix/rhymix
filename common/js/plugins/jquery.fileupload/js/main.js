(function($){
	"use strict";

	var default_settings = {
		autoUpload: true,
		dataType: 'json',
		sequentialUploads: true,

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
		actSetCover : '.xefu-act-set-cover',

		tmplXeUploaderFileitem : '<li class="xefu-file xe-clearfix" data-file-srl="{{file_srl}}">' +
			'<span class="xefu-file-name">{{source_filename}}</span>' +
			'<span class="xefu-file-info">' +
				'<span>{{disp_file_size}}</span><span><input type="checkbox" data-file-srl="{{file_srl}}"></span>' +
			'</span></li>',
		tmplXeUploaderFileitemImage: '<li class="xefu-file xefu-file-image {{#if cover_image}}xefu-is-cover-image{{/if}}" data-file-srl="{{file_srl}}">' +
			'<strong class="xefu-file-name">{{source_filename}}</strong>' +
			'<span class="xefu-file-info">' +
				'<span class="xefu-file-size">{{disp_file_size}}</span>' +
				'<span>' +
					'<span class="xefu-thumbnail" style="background-image:url({{thumbnail_url}})" title="{{source_filename}}"></span>' +
				'</span>' +
				'<span><input type="checkbox" data-file-srl="{{file_srl}}"></span>' +
				'<button class="xefu-act-set-cover" data-file-srl="{{file_srl}}" title="Set as cover image"><i class="xi-check-circle"></i></button>' +
			'</span></li>',
		tmplXeUploaderFileitemVideo: '<li class="xefu-file xefu-file-image {{#if cover_image}}xefu-is-cover-image{{/if}}" data-file-srl="{{file_srl}}">' +
			'<strong class="xefu-file-name">{{source_filename}}</strong>' +
			'<span class="xefu-file-info">' +
				'<span class="xefu-file-size">{{disp_file_size}}</span>' +
				'<span>' +
					'<span class="xefu-file-video"><span class="xefu-file-video-play"></span></span>' +
					'<span class="xefu-thumbnail" style="background-image:url({{thumbnail_url}})" title="{{source_filename}}"></span>' +
				'</span>' +
				'<span><input type="checkbox" data-file-srl="{{file_srl}}"></span>' +
				'<button class="xefu-act-set-cover" data-file-srl="{{file_srl}}" title="Set as cover image"><i class="xi-check-circle"></i></button>' +
			'</span></li>'
	};

	var _elements = [
		'fileList',
		'actSelectedInsertContent',
		'actSelectedDeleteFile',
		'actDeleteFile',
		'actSetCover',
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
		settings: {},
		init : function() {
		},
		deactivate: function() {
		},
		createInstance: function(containerEl, opt) {
			var self = this;
			var $container = containerEl;
			var data = $container.data();
			var lastUploadTime = 0;

			$.extend(data, {
				files: {},
				selected_files: {},
				settings: {},
				last_selected_file: null,
			});

			var currentEnforce_ssl = window.enforce_ssl;
			if(location.protocol == 'https:') { window.enforce_ssl = true; }
			
			var chunkStatus = true;
			var defaultFormData = {
				"editor_sequence": data.editorSequence,
				"upload_target_srl" : data.uploadTargetSrl,
				"mid" : window.current_mid,
				"act": 'procFileUpload'
			};

			var settings = {
				url: request_uri,
				formData: defaultFormData,
				dropZone: $container,
				add: function(e, item) {
					var dfd = jQuery.Deferred();

					$.each(item.files, function(index, file) {
						if(data.settings.maxFileSize > 0 && data.settings.maxFileSize < file.size) {
							dfd.reject();
							alert(window.xe.msg_exceeds_limit_size);
							return false;
						}
						dfd.resolve();
					});

					dfd.done(function(){
						item.submit();
					});
				},
				submit: function(e, item) {
					item.formData = defaultFormData;
					chunkStatus = true;
				},
				chunksend: function(e, res) {
					lastUploadTime = Date.now();
					if (!chunkStatus) {
						return false;
					}
				},
				chunkdone: function(e, res) {
					lastUploadTime = Date.now();
					if (res.result) {
						if (res.result.error != 0) {
							if (res.result.message) {
								alert(res.result.message);
							} else {
								alert(window.xe.msg_file_upload_error + " (Type 1)");
							}
							return chunkStatus = false;
						}
					} else {
						alert(window.xe.msg_file_upload_error + " (Type 2)");
						return chunkStatus = false;
					}
				},
				chunkfail: function(e, res) {
					lastUploadTime = Date.now();
					if (chunkStatus) {
						alert(window.xe.msg_file_upload_error + " (Type 3)" + "<br>\n" + res.errorThrown + "<br>\n" + res.textStatus);
						return chunkStatus = false;
					}
				},
				done: function(e, res) {
					lastUploadTime = Date.now();
					data.settings.progressbarGraph.width('100%');
					data.settings.progressPercent.text('100%');
					setTimeout(function() {
						if (lastUploadTime < Date.now() - 800) {
							data.settings.progressbar.slideUp();
							data.settings.progressStatus.slideUp();
						}
					}, 1000);
					var result = res.response().result;
					var temp_code = '';
					if (!result) {
						alert(window.xe.msg_file_upload_error + " (Type 4)");
						return false;
					}
					if (!jQuery.isPlainObject(result)) {
						result = jQuery.parseJSON(result);
					}
					if (!result) {
						alert(window.xe.msg_file_upload_error + " (Type 5)" + "<br>\n" + res.response().result);
						return false;
					}

					if(result.error == 0) {
						if(/\.(gif|jpe?g|png|webp)$/i.test(result.source_filename) && opt.autoinsertTypes.image) {
							temp_code += '<img src="' + result.download_url + '" alt="' + result.source_filename + '" editor_component="image_link" data-file-srl="' + result.file_srl + '" />';
						}
						else if(/\.(mp3|wav|ogg|flac|aac)$/i.test(result.source_filename) && opt.autoinsertTypes.audio) {
							temp_code += '<audio src="' + result.download_url + '" controls data-file-srl="' + result.file_srl + '" />';
						}
						else if(/\.(mp4|webm|ogv)$/i.test(result.source_filename) && opt.autoinsertTypes.video) {
							if(result.original_type === 'image/gif') {
								temp_code += '<video src="' + result.download_url + '" autoplay loop muted playsinline data-file-srl="' + result.file_srl + '" />';
							} else if (result.download_url.match(/\bprocFileDownload\b/)) {
								if (result.download_url.match(/^\?/)) {
									result.download_url = XE.URI(default_url).pathname() + result.download_url;
								}
								temp_code += '<video src="' + result.download_url + '" controls preload="none" data-file-srl="' + result.file_srl + '" />';
							} else {
								temp_code += '<video src="' + result.download_url + '" controls data-file-srl="' + result.file_srl + '" />';
							}
							if(result.thumbnail_filename) {
								temp_code = temp_code.replace('controls', 'poster="' + result.thumbnail_filename.replace(/^.\//, XE.URI(default_url).pathname()) + '" controls');
							}
						}
						
						if(temp_code !== '') {
							if (opt.autoinsertPosition === 'paragraph') {
								temp_code = "<p>" + temp_code + "</p>\n";
							}
							try {
								_getCkeInstance(settings.formData.editor_sequence).insertHtml(temp_code, "unfiltered_html");
							}
							catch(err) {}
						}
					} else if (result.message) {
						alert(result.message);
						return false;
					} else {
						alert(window.xe.msg_file_upload_error + " (Type 6)" + "<br>\n" + res.response().result);
						return false;
					}
				},
				fail: function(e, res) {
					lastUploadTime = Date.now();
					setTimeout(function() {
						if (lastUploadTime < Date.now() - 800) {
							data.settings.progressbar.slideUp();
							data.settings.progressStatus.slideUp();
						}
					}, 1000);
					if (chunkStatus) {
						alert(window.xe.msg_file_upload_error + " (Type 7)" + "<br>\n" + res.errorThrown + "<br>\n" + res.textStatus);
						return false;
					}
				},
				stop: function() {
					lastUploadTime = Date.now();
					self.loadFilelist($container);
				},
				start: function() {
					lastUploadTime = Date.now();
					data.settings.progressbarGraph.width(0);
					data.settings.progressStatus.show();
					data.settings.progressbar.show();
				},
				progressall: function (e, res) {
					lastUploadTime = Date.now();
					var progress = Math.round(res.loaded / res.total * 999) / 10;
					data.settings.progressbarGraph.width(progress+'%');
					data.settings.progressPercent.text(progress+'%');
				}
			};
			window.enforce_ssl = currentEnforce_ssl;


			data.settings = $.extend({} , default_settings, settings, opt || {});
			$container.data(data);

			$.each(_elements, function(idx, val) {
				if(typeof data.settings[val] === 'string') data.settings[val] = $container.find(data.settings[val]);
			});

			var INS = $container.fileupload(data.settings)
			.prop('disabled', !$.support.fileInput)
			.parent()
			.addClass($.support.fileInput ? undefined : 'disabled');

			$container.data('xefu-instance', this);

			// 파일 목록 불러오기
			this.loadFilelist($container);

			// 본문 삽입
			data.settings.actSelectedInsertContent.on('click', function() {
				self.insertToContent($container);
				data.settings.fileList.finderSelect('unHighlightAll');
			});

			// 파일 삭제
			data.settings.actSelectedDeleteFile.on('click', function() {
				self.deleteFile($container);
			});

			// finderSelect
			var fileselect = data.settings.fileList.finderSelect({children:"li", enableDesktopCtrlDefault:true});
			data.settings.fileList.on("mousedown", 'img', function(e){ e.preventDefault(); });

			fileselect.finderSelect('addHook','highlight:after', function(el) {
				el.find('input').prop('checked', true);
				var selected = data.settings.fileList.find('input:checked');
				data.selected_files = selected;
			});

			fileselect.finderSelect('addHook','unHighlight:after', function(el) {
				el.find('input').prop('checked', false);
				var selected = data.settings.fileList.find('input:checked');
				data.selected_files = selected;
			});

			fileselect.on("click", ":checkbox", function(e){
				e.preventDefault();
			});

			fileselect.on("click", ".xefu-act-set-cover", function(e){
				e.preventDefault();
				self.setCover($container, e.currentTarget);
			});


			$(document).bind('dragover', function (e) {
				var timeout = window.dropZoneTimeout,
				dropZone = data.settings.dropZone;

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

			$container.data(data);
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

		insertToContent: function($container) {
			var self = this;
			var data = $container.data();

			$.each(data.selected_files, function(idx, file) {
				var file_srl = $(file).data().fileSrl;
				var result = data.files[file_srl];
				if(!result) return;
				var temp_code = '';

				if(/\.(gif|jpe?g|png|webp)$/i.test(result.source_filename)) {
					temp_code += '<img src="' + result.download_url + '" alt="' + result.source_filename + '" editor_component="image_link" data-file-srl="' + result.file_srl + '" />';
				}
				else if(/\.(mp3|wav|ogg|flac|aac)$/i.test(result.source_filename)) {
					temp_code += '<audio src="' + result.download_url + '" controls data-file-srl="' + result.file_srl + '" />';
				}
				else if(/\.(mp4|webm|ogv)$/i.test(result.source_filename)) {
					if(result.original_type === 'image/gif') {
						temp_code += '<video src="' + result.download_url + '" autoplay loop muted playsinline data-file-srl="' + result.file_srl + '" />';
					} else if (result.download_url.match(/\bprocFileDownload\b/)) {
						if (result.download_url.match(/^\?/)) {
							result.download_url = XE.URI(default_url).pathname() + result.download_url;
						}
						temp_code += '<video src="' + result.download_url + '" controls preload="none" data-file-srl="' + result.file_srl + '" />';
					} else {
						temp_code += '<video src="' + result.download_url + '" controls data-file-srl="' + result.file_srl + '" />';
					}
					if(result.thumbnail_filename) {
						temp_code = temp_code.replace('controls', 'poster="' + result.thumbnail_filename.replace(/^.\//, XE.URI(default_url).pathname()) + '" controls');
					}
				}
				
				if(temp_code !== '') {
					if (data.settings.autoinsertPosition === 'paragraph') {
						temp_code = "<p>" + temp_code + "</p>\n";
					}
				}
				if(temp_code === '') {
					temp_code += '<a href="' + result.download_url + '" data-file-srl="' + result.file_srl + '">' + result.source_filename + "</a>\n";
				}
				try {
					_getCkeInstance(data.editorSequence).insertHtml(temp_code, "unfiltered_html");
				}
				catch(err) {}
				
			});
		},
		/**
		 * 지정된 하나의 파일 또는 다중 선택된 파일 삭제
		 */
		deleteFile: function($container, file_srl) {
			var self = this;
			var file_srls = [];
			var data = $container.data();

			if(!file_srl)
			{
				$.each(data.selected_files, function(idx, file) {
					if(!file) return;

					var file_srl = $(file).data().fileSrl;

					file_srls.push(file_srl);
				});
			}
			else
			{
				file_srls.push(file_srl);
			}

			exec_json('file.procFileDelete', {'file_srls': file_srls.join(','), 'editor_sequence': data.editorSequence}, function() {
				$.each(file_srls, function(idx, srl){
					data.settings.fileList.find('ul').find('li[data-file-srl=' + srl + ']').remove();
				});
				var ckeditor = _getCkeInstance(data.editorSequence);
				var regexp = new RegExp('<(img|audio|video) [^>]*data-file-srl="(' + file_srls.join('|') + ')"[^>]*>', 'g');
				ckeditor.setData(ckeditor.getData().replace(regexp, ''));
				self.loadFilelist($container);
			});
		 },
		/**
		 * 파일 목록 갱신
		 */
		loadFilelist: function($container) {
			var self = this;
			var data = $container.data();
			var obj = {};
			obj.mid = window.current_mid;
			obj.editor_sequence = data.editorSequence;
			obj.allow_chunks = 'Y';

			$.exec_json('file.getFileList', obj, function(res){
				data.uploadTargetSrl = res.upload_target_srl;
				if(editorRelKeys[data.editorSequence]) {
					editorRelKeys[data.editorSequence].primary.value = res.upload_target_srl;
				}
				data.uploadTargetSrl = res.uploadTargetSrl;

				// @TODO 정리
				$container.find('.allowed_filetypes').text(res.allowed_extensions.join(', '));
				$container.find('.allowed_filesize').text(res.allowed_filesize);
				$container.find('.allowed_attach_size').text(res.allowed_attach_size);
				$container.find('.attached_size').text(res.attached_size);
				$container.find('.file_count').text(res.files.length);
				if(res.allowed_extensions.length == 0) {
					$container.find('.allowed_filetypes_container').hide();
				} else {
					$container.find('.allowed_filetypes_container').show();
				}

				var tmpl_fileitem = data.settings.tmplXeUploaderFileitem;
				var tmpl_fileitem_image = data.settings.tmplXeUploaderFileitemImage;
				var tmpl_fileitem_video = data.settings.tmplXeUploaderFileitemVideo;
				var template_fileimte = Handlebars.compile(tmpl_fileitem);
				var template_fileimte_image = Handlebars.compile(tmpl_fileitem_image);
				var template_fileimte_video = Handlebars.compile(tmpl_fileitem_video);
				var result_image = [];
				var result = [];

				// 첨부된 파일이 없으면 감춤
				if(!res.files.length) {
					data.settings.fileList.hide();
					data.settings.controll.hide();
					return;
				}

				// 이미지와 그외 파일 분리
				$.each(res.files, function (index, file) {
					if(data.files[file.file_srl]) return;
					
					data.files[file.file_srl] = file;
					$container.data(data);
					
					file.thumbnail_url = file.download_url;
					file.source_filename = file.source_filename.replace("&amp;", "&");
					
					if(file.thumbnail_filename) {
						file.thumbnail_url = file.thumbnail_filename;
						if(/\.(mp4|webm|ogv)$/i.test(file.source_filename)) {
							result_image.push(template_fileimte_video(file));
						} else {
							result_image.push(template_fileimte_image(file));
						}
					}
					else if(/\.(gif|jpe?g|png|webp)$/i.test(file.source_filename)) {
						result_image.push(template_fileimte_image(file));
					} else {
						result.push(template_fileimte(file));
					}
				});

				// 파일 목록
				data.settings.filelistImages.append(result_image.join(''));
				data.settings.filelist.append(result.join(''));

				// 컨트롤, 리스트 표시
				data.settings.controll.show()
				data.settings.fileList.show();
			});
		},
		setCover: function($container, selected_el) {
			var data = $container.data();
			var $el = $(selected_el);
			var file_srl = $el.data().fileSrl;

			exec_json('file.procFileSetCoverImage', {'file_srl' : file_srl, 'mid' : window.current_mid, 'editor_sequence' : data.editorSequence}, function(res) {
				if(res.error != 0) return;

				data.settings.filelistImages.find('li').removeClass('xefu-is-cover-image');
				var $parentLi = $el.closest('li');

				if(res.is_cover == 'N') {

				$parentLi.removeClass('xefu-is-cover-image');

				} else if(res.is_cover == 'Y') {

				$parentLi.addClass('xefu-is-cover-image');

				}

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
})(jQuery);
