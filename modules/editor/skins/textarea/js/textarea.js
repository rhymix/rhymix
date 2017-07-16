function editorTextarea(editor_sequence) {
	var textarea = jQuery("#textarea_instance_" + editor_sequence);
	var content_key = textarea.data("editor-content-key-name");
	var primary_key = textarea.data("editor-primary-key-name");
	var insert_form = textarea.closest("form");
	var content_input = insert_form.find("input[name='" + content_key + "']");
	
	// Set editor keys
    editorRelKeys[editor_sequence] = {};
    editorRelKeys[editor_sequence].primary = insert_form.find("input[name='" + primary_key + "']");
	editorRelKeys[editor_sequence].content = content_input;
    editorRelKeys[editor_sequence].func = editorGetContent;
	
	// Set editor_sequence
	insert_form[0].setAttribute('editor_sequence', editor_sequence);
	
	// Load existing content
	if (content_input.size()) {
		var content = String(content_input.val()).stripTags();
		content_input.val(content);
		textarea.val(content.unescape());
	}
	
	// Save edited content
	textarea.on("change", function() {
		content_input.val(String(jQuery(this).val()).escape());
	});
}