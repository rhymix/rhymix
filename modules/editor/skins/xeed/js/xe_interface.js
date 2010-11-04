(function($){

if (!window.xe)  xe = {};
if (!xe.Editors) xe.Editors = [];

function editorStart_xe(editor_seq, primary_key, content_key, editor_height, colorset, content_style, content_font, content_font_size) {
	var $textarea, $form, $input, saved_str, xeed, as;

	if(typeof(colorset)=='undefined') colorset = 'white';
	if(typeof(content_style)=='undefined') content_style = 'xeStyle';
	if(typeof(content_font)=='undefined') content_font= '';
	if(typeof(content_font_size)=='undefined') content_font_size= '';

	$textarea = $('#xeed-'+editor_seq);
	$form     = $($textarea[0].form).attr('editor_sequence', editor_seq);
	$input    = $form.find('input[name='+content_key+']');

	if($input[0]) $textarea.val($input.remove().val()).attr('name', content_key);
	$textarea.attr('name', content_key);

	// create an editor
	xe.Editors[editor_seq] = xeed = new xe.Xeed($textarea);
	xe.registerApp(xeed);

	// 자동 저장 사용
	if (as=form.elements['_saved_doc_title']) {
		//xeed.registerPlugin(new xe.XE_AutoSave(oIRTextarea, elAppContainer));
	}

	// Set standard API
	editorRelKeys[editor_seq] = {
		primary   : $form[0][primary_key],
		content   : $form[0][content_key],
		editor    : xeed,
		func      : function(){ return xeed.cast('GET_CONTENT'); },
		pasteHTML : function(text){ xeed.cast('PASTE_HTML', [text]); }
	};

	return xeed;
}

window.editorStart_xe = editorStart_xe;

})(jQuery);

function editorGetAutoSavedDoc(form) {
	var param = new Array();
	param['mid'] = current_mid;
	param['editor_sequence'] = form.getAttribute('editor_sequence')
	setTimeout(function() {
	  var response_tags = new Array("error","message","editor_sequence","title","content","document_srl");
	  exec_xml('editor',"procEditorLoadSavedDocument", param, function(a,b,c) { editorRelKeys[param['editor_sequence']]['primary'].value = a['document_srl']; if(typeof(uploadSettingObj[param['editor_sequence']]) == 'object') editorUploadInit(uploadSettingObj[param['editor_sequence']], true); }, response_tags);
	}, 0);
	
}
