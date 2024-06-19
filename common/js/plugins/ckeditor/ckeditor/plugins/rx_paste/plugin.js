'use strict';

/**
 * Upload plugin for Rhymix
 */
CKEDITOR.plugins.add('rx_paste', {

	init: function(editor) {

		/**
		 * Prevent the clipboard plugin from interfering with us.
		 */
		editor.plugins.clipboard._supportedFileMatchers.unshift(function() { return true; });
		if (editor.config.clipboard_handleImages) {
			editor.config.clipboard_handleImages = false;
		}

		/**
		 * The main event handler for paste and drop.
		 */
		editor.on('paste', function(event) {

			const method = event.data.method;
			const dataValue = event.data.dataValue;
			const dataTransfer = event.data.dataTransfer;
			const filesCount = dataTransfer.getFilesCount();
			const isFileTransfer = dataTransfer.isFileTransfer() || String(dataValue).match(/^<img\s[^>]+>$/);

			// Replace iframe code in pasted text.
			if (method === 'paste' && dataValue && dataValue.replace) {
				event.data.dataValue = dataValue.replace(/&lt;(iframe|object)\s[^<>]+&lt;\/\1&gt;/g, function(m) {
					return String(m).unescape() + '<p>&nbsp;</p>';
				});
			}

			// Check if the pasted data contains any files.
			if (filesCount && isFileTransfer) {
				event.stop();
				for (let i = 0; i < filesCount; i++) {
					uploadFile(dataTransfer.getFile(i));
				}
			}
		});

		/**
		 * Upload function.
		 */
		const uploadFile = function(file) {

			// Get the editor sequence.
			const editor_sequence = editor.config.xe_editor_sequence;
			const editor_container = $('#ckeditor_instance_' + editor_sequence);
			const upload_container = editor_container.nextAll('.xefu-container').first();

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
					if (data.error == 0) {
						insertFile(upload_container, data);
						reloadFileList(upload_container, data);
					} else {
						displayError(8, data.message);
					}
				},
				error: function(jqXHR) {
					displayError(9, jqXHR.responseText);
				}
			});
		};

		/**
		 * Insert file into editor.
		 */
		const insertFile = function(container, data) {
			const html = container.data('instance').generateHtml(container, data);
			editor.insertHtml(html, 'unfiltered_html');
		};

		/**
		 * Reload the file list.
		 */
		const reloadFileList = function(container, data) {
			container.data('editorStatus', data);
			container.data('instance').loadFilelist(container, true);
		};

		/**
		 * Display an error message.
		 */
		const displayError = function(type, message) {
			alert(window.xe.msg_file_upload_error + ' (Type ' + type + ")\n" + message);
		};

	}

});
