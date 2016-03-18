function _getCkeInstance(editor_sequence) {
	var $editor_area = jQuery("#ckeditor_instance_"+editor_sequence);
	return $editor_area.data('cke_instance');
}

//Get content from editor
function editorGetContentTextarea_xe(editor_sequence) {
	return _getCkeInstance(editor_sequence).getText();
}


function editorGetSelectedHtml(editor_sequence) {
	return _getCkeInstance(editor_sequence).getSelection().getSelectedText();
}

function editorGetContent(editor_sequence) {
	return _getCkeInstance(editor_sequence).getData();
}

//Replace html content to editor
function editorReplaceHTML(iframe_obj, content) {
	var editor_sequence = parseInt(iframe_obj.id.replace(/^.*_/, ''), 10);

	_getCkeInstance(editor_sequence).insertHtml(content, "unfiltered_html");
}

function editorGetIFrame(editor_sequence) {
	return jQuery('#ckeditor_instance_' + editor_sequence).get(0);
}
