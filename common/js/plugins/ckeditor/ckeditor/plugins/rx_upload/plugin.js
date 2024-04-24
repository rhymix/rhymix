/**
 * Upload plugin for Rhymix
 */
CKEDITOR.plugins.add('rx_upload', {
	init: function(editor) {

		/**
		 * The main event handler for paste and drop.
		 */
		editor.on('paste', function(event) {

			// Check if the pasted data contains any files.
			const method = event.data.method;
			const dataTransfer = event.data.dataTransfer;
			const files_count = dataTransfer.getFilesCount();
			if (!files_count) {
				return;
			}

			// Prevent the default plugin from touching these files.
			event.stop();

			// Read and upload each file.
			for (let i = 0; i < files_count; i++) {
				uploadFile(dataTransfer.getFile(i));
			}
		});

		/**
		 * Upload function.
		 */
		const uploadFile = function(file) {

			// Get the editor sequence.
			const editor_container = $(editor.container.$).parents('.rx_ckeditor');
			const upload_container = editor_container.next('.xefu-container');
			const editor_sequence = editor_container.data('editorSequence');

			// Generate the form data.
			const form = new FormData();
			form.append('mid', window.editor_mid ? window.editor_mid : window.current_mid);
			form.append('act', 'procFileUpload');
			form.append('editor_sequence', editor_sequence);
			form.append('Filedata', file);

			// Upload!
			$.ajax({
				url: window.request_uri,
				type: 'POST',
				contentType: false,
				processData: false,
				cache: false,
				data: form,
				dataType: 'json',
				success: function(data) {
					insertFile(upload_container, data);
					reloadFileList(upload_container, data);
				},
				error: function(jqXHR) {
					alert('Upload error: ' + jqXHR.responseText);
				}
			});
		};

		/**
		 * Insert file into editor.
		 */
		const insertFile = function(container, data) {
			if (data.error != 0) {
				alert(data.message);
				return;
			}

			let temp_code = '';
			if(/\.(gif|jpe?g|png|webp)$/i.test(data.source_filename)) {
				temp_code += '<img src="' + data.download_url + '" alt="' + data.source_filename + '" editor_component="image_link" data-file-srl="' + data.file_srl + '" />';
			}
			else if(/\.(mp3|wav|ogg|flac|aac)$/i.test(data.source_filename)) {
				temp_code += '<audio src="' + data.download_url + '" controls data-file-srl="' + data.file_srl + '" />';
			}
			else if(/\.(mp4|webm|ogv)$/i.test(data.source_filename)) {
				if(data.original_type === 'image/gif') {
					temp_code += '<video src="' + data.download_url + '" autoplay loop muted playsinline data-file-srl="' + data.file_srl + '" />';
				} else if (data.download_url.match(/\b(?:procFileDownload\b|files\/download\/)/)) {
					if (!data.download_url.match(/^\//)) {
						data.download_url = XE.URI(default_url).pathname() + data.download_url;
					}
					temp_code += '<video src="' + data.download_url + '" controls preload="none" data-file-srl="' + data.file_srl + '" />';
				} else {
					temp_code += '<video src="' + data.download_url + '" controls data-file-srl="' + data.file_srl + '" />';
				}
				if(data.thumbnail_filename) {
					temp_code = temp_code.replace('controls', 'poster="' + data.thumbnail_filename.replace(/^.\//, XE.URI(default_url).pathname()) + '" controls');
				}
			}
			else {
				temp_code += '<a href="' + data.download_url + '" data-file-srl="' + data.file_srl + '">' + data.source_filename + "</a>\n";
			}

			if(temp_code !== '') {
				temp_code = "<p>" + temp_code + "</p>\n";
			}

			editor.insertHtml(temp_code, 'unfiltered_html');
		};

		/**
		 * Reload the file list.
		 */
		const reloadFileList = function(container, data) {
			container.data('editorStatus', data);
			container.data('instance').loadFilelist(container, true);
		};

	}
});
