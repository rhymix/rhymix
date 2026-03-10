'use strict';

/**
 * Initialize each instance of file uploader on the page.
 */
$(function() {
	$('.xefu-container').each(function() {
		const container = $(this);
		const data = container.data();
		container.data('instance', container.xeUploader({
			maxFileSize: parseInt(data.maxFileSize, 10),
			maxChunkSize: parseInt(data.maxChunkSize, 10),
			preConversionSize: parseInt(data.preConversionSize || 0, 10),
			preConversionTypes: data.preConversionTypes ? data.preConversionTypes.split(',') : [],
			autoinsertTypes: data.autoinsertTypes,
			autoinsertPosition: data.autoinsertPosition,
			singleFileUploads: true
		}));
	});
});

/**
 * This function is only retained for backward compatibility.
 * Do not depend on it for any reason.
 */
function reloadUploader(editor_sequence) {
	var container = $('#xefu-container-' + editor_sequence);
	if (container.length) {
		container.data('instance').loadFilelist(container);
	}
}
