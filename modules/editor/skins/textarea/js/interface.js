function _getTextareaInstance(editor_sequence) {
	return jQuery('#textarea_instance_' + editor_sequence);
}

function editorGetContent(editor_sequence) {
	return _getTextareaInstance(editor_sequence).val().escape();
}

function editorReplaceHTML(iframe_obj, content) {
	var editor_sequence = parseInt(iframe_obj.id.replace(/^.*_/, ''), 10);
	_getTextareaInstance(editor_sequence).val(String(content).stripTags());
}

function editorGetIFrame(editor_sequence) {
	return _getTextareaInstance(editor_sequence).get(0);
}
