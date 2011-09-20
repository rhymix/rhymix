/**
 * popup으로 열렸을 경우 부모창의 위지윅에디터에 select된 멀티미디어 컴포넌트 코드를 체크하여
 * 있으면 가져와서 원하는 곳에 삽입
 **/
jQuery(function($){

(function(){
	if(!is_def(opener)) return;

	var $node = $(opener.editorPrevNode).filter('img'), attrs;
	if(!$node.length) return;

	attrs = {
		url     : $node.attr('multimedia_src'),
		caption : $node.attr('alt'),
		width   : $node.width() - 4,
		height  : $node.height() - 4,
		wmode   : $node.attr('wmode')
	};

	$.each(attrs, function(key, val) {
		get_by_id('multimedia_'+key).value = val;
	});

})();

$('a._insert').click(function(){
	if(!is_def(opener)) return;

	var el_wmode = get_by_id('fo').elements['multimedia_wmode'];
	var attrs = {
		alt    : get_by_id('multimedia_caption').value,
		width  : get_by_id('multimedia_width').value || 400,
		height : get_by_id('multimedia_height').value || 400,
		wmode  : el_wmode.value || el_wmode.options[0].value,
		auto_start : get_by_id('multimedia_auto_start').checked?'true':'false',
		multimedia_src : get_by_id('multimedia_url').value.replace(request_uri, '')
	};

	if(!attrs['multimedia_src']) {
	  window.close();
	  return;
	}

	var html = '<img src="../../../../common/img/blank.gif" editor_component="multimedia_link" multimedia_src="" width="" height="" wmode="" style="display:block;width:'+attrs.width+'px;height:'+attrs.height+'px;border:2px dotted #4371B9;background:url(./modules/editor/components/multimedia_link/tpl/multimedia_link_component.gif) no-repeat center" auto_start="" alt="" />';

	html = html.replace(/(\w+)=""/, function(m0,m1) {
		return attrs[m1] ? (m1+'="'+attrs[m1]+'"') : '';
	});

	opener.editorFocus(opener.editorPrevSrl);

	var iframe_obj = opener.editorGetIFrame(opener.editorPrevSrl)

	opener.editorReplaceHTML(iframe_obj, html);
	opener.editorFocus(opener.editorPrevSrl);

	window.close();
});

});
