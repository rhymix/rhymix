var is_popup = null;

/**
 * @brief Get emoticon list by name
 * @params String emoticon name
 */
function getEmoticons(emoName) {
	var params    = {component:'emoticon', emoticon:emoName, method:'getEmoticonList'};
	var resp_tags = 'error message emoticons'.split(' ');

	exec_xml('editor', 'procEditorCall', params, completeGetEmoticons, resp_tags);
}

/**
 * @brief Load callback
 */
function completeGetEmoticons(ret_obj) {
	var emoticons = ret_obj.emoticons.item;
	var html = [];
	for(var i=0;i<emoticons.length;i++) {
		var $img, $div;
		$img = $('<input type="image" class="emoticon" onclick="insertEmoticon(this);return false" />')
			.width( parseInt(emoticons[i].width,  10))
			.height(parseInt(emoticons[i].height, 10))
			.attr({
				'src': emoticons[i].url,
				'data-src': emoticons[i].url,
				'alt': emoticons[i].alt
			});
		if( emoticons[i].svg ) {
			$img.attr({
				'data-svg': emoticons[i].svg
			});
			if( typeof SVGRect !== "undefined" ) {
				$img.attr({
					'src': emoticons[i].svg
				});
			}
		}
		$div = $('<div>');
		html[html.length] = $div.append($img).html();
		$img = null;
		$div = null;
	}
	$('#emoticons').html(html.join(''));
	setFixedPopupSize();
}

/**
 * @brief  Insert a selected emoticon into the document
 * @params Event jQuery event
 */
function insertEmoticon(obj) {
	var $img, $div, html, iframe, win = is_popup?opener:window;

	if(!win) return;

	$img = $('<img>').addClass("emoticon").width($(obj).width()).height($(obj).height()).attr({'src': $(obj).attr('data-src'), 'alt': $(obj).attr('alt')});
	//https://developer.mozilla.org/en-US/docs/Learn/HTML/Multimedia_and_embedding/Adding_vector_graphics_to_the_Web#Troubleshooting_and_cross-browser_support
	if($(obj).attr('data-svg')) {
		$img.attr('srcset', $(obj).attr('data-svg'));
	}
	$div = $('<div>');
	html = $div.append($img).html();

	win.editorFocus(win.editorPrevSrl);
	win.editorRelKeys[win.editorPrevSrl].pasteHTML(html);

	if (is_popup) window.focus();
	
	rhymix_alert(lang_success_added);
	return false;
}

$(function(){
	is_popup = window._isPoped;
	// load default emoticon set
	getEmoticons('Twemoji');
	$('ul.rx_tab>li a').click(function(){
		$list = $( this ).parent('li');
		$list.siblings('.rx_active').removeClass('rx_active');
		$list.addClass('rx_active'); 
	});

});
