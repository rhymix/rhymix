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
	content = editorReplacePath(content);

	var editor_sequence = parseInt(iframe_obj.id.replace(/^.*_/, ''), 10);

	_getCkeInstance(editor_sequence).insertHtml(content, "unfiltered_html");
}

function editorGetIFrame(editor_sequence) {
	return jQuery('#ckeditor_instance_' + editor_sequence).get(0);
}


function editorReplacePath(content) {
	// 태그 내 src, href, url의 XE 상대경로를 http로 시작하는 full path로 변경
	content = content.replace(/\<([^\>\<]*)(src=|href=|url\()("|\')*([^"\'\)]+)("|\'|\))*(\s|>)*/ig, function(m0,m1,m2,m3,m4,m5,m6) {
		if(m2=="url(") { m3=''; m5=')'; } else { if(typeof(m3)=='undefined') m3 = '"'; if(typeof(m5)=='undefined') m5 = '"'; if(typeof(m6)=='undefined') m6 = ''; }
		var val = jQuery.trim(m4).replace(/^\.\//,'');
		if(/^(http\:|https\:|ftp\:|telnet\:|mms\:|mailto\:|\/|\.\.|\#)/i.test(val)) return m0;
		return '<'+m1+m2+m3+request_uri+val+m5+m6;
	});

	return content;
}
