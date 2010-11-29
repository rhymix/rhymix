(function($){

if (!window.xe)  xe = {};
if (!xe.Editors) xe.Editors = [];

function editorStart_xe(editor_seq, primary_key, content_key, editor_height, colorset, content_style, content_font, content_font_size) {
	var $textarea, $form, $input, saved_str, xeed, opt = {};

	if(typeof(colorset)=='undefined') colorset = 'white';
	if(typeof(content_style)=='undefined') content_style = 'xeStyle';
	if(typeof(content_font)=='undefined') content_font= '';
	if(typeof(content_font_size)=='undefined') content_font_size= '';

	$textarea = $('#xeed-'+editor_seq);
	$form     = $($textarea[0].form).attr('editor_sequence', editor_seq);
	$input    = $form.find('input[name='+content_key+']');

	if($input[0]) $textarea.val($input.remove().val()).attr('name', content_key);
	$textarea.attr('name', content_key);
	
	// Use auto save feature?
	opt.use_autosave = !!$form[0].elements['_saved_doc_title'];
	
	// min height
	if (editor_height) opt.height = parseInt(editor_height) || 200;

	// create an editor
	xe.Editors[editor_seq] = xeed = new xe.Xeed($textarea, opt);
	xe.registerApp(xeed);
	
	// filters
	xeed.cast('REGISTER_FILTER', ['r2t', plz_standard]);
	xeed.cast('REGISTER_FILTER', ['r2t', remove_baseurl]);

	// Set standard API
	editorRelKeys[editor_seq] = {
		primary   : $form[0][primary_key],
		content   : $form[0][content_key],
		editor    : xeed,
		func      : function(){ return xeed.cast('GET_CONTENT'); },
		pasteHTML : function(text){ xeed.cast('PASTE_HTML', [text]); }
	};
	
	// Auto save
	if (opt.use_autosave) xeed.registerPlugin(new xe.Xeed.AutoSave());

	return xeed;
}

// standard filters
function plz_standard(code) {
	var single_tags = 'img input'.split(' ');

	code = code.replace(/<(\/)?([A-Za-z0-9:]+)(.*?)(\s*\/?)>/g, function(m0,is_close,tag,attrs,closing){
		tag = tag.toLowerCase();
		
		attrs = attrs.replace(/([\w:-]\s*)=(?:([^"' \t\r\n]+)|\s*("[^"]*")|\s*('[^']*'))/g, function(m0,name,m2,m3,m4){
			var val = m2||m3||m4;

			if (m3||m4) val = val.substr(1,val.length-2);
		
			return $.trim(name.toLowerCase())+'='+'"'+val+'"';
		});

		if (attrs=$.trim(attrs)) attrs = ' '+attrs;
	
		closing = $.trim(closing);
		if (!is_close && !closing && $.inArray(tag, single_tags) != -1) closing = ' /';

		return '<'+(is_close||'')+tag+attrs+closing+'>';
	});

	return code;
}

// remove base url
function remove_baseurl(code) {
	var reg = new RegExp(' (href|src)\s*=\s*(["\'])?'+request_uri.replace('\\', '\\\\'), 'ig');

	return code.replace(reg, function(m0,m1,m2){ return ' '+m1+'='+m2; });
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
