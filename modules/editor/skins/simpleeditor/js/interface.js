function _getSimpleEditorInstance(editor_sequence) {
	return jQuery('#simpleeditor_instance_' + editor_sequence);
}

function editorGetContent(editor_sequence) {
	return _getSimpleEditorInstance(editor_sequence).html().escape();
}

function editorReplaceHTML(iframe_obj, content) {
	var editor_sequence = parseInt(iframe_obj.id.replace(/^.*_/, ''), 10);
	_getSimpleEditorInstance(editor_sequence).html(content);
}

function editorGetIFrame(editor_sequence) {
	return _getSimpleEditorInstance(editor_sequence).get(0);
}
