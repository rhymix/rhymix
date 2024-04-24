'use strict';

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
