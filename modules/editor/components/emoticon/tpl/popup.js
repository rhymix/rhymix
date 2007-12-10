function insertEmoticon(obj) {
    if(typeof(opener)=='undefined') return;

    var url = obj.src.replace(request_uri,'');
	var text = "<img editor_component=\"emoticon\" src=\""+url+"\" alt=\"emoticon\">";
	
    opener.editorFocus(opener.editorPrevSrl);

    var iframe_obj = opener.editorGetIFrame(opener.editorPrevSrl)

    opener.editorReplaceHTML(iframe_obj, text);
    opener.editorFocus(opener.editorPrevSrl);
}

/* 선택된 이모티콘 목록을 가져옴 */
function getEmoticons(emoticon) {
  var params = new Array();
  params['component'] = "emoticon";
  params['emoticon'] = emoticon;
  params['method'] = "getEmoticonList";

  var response_tags = new Array('error','message','emoticons');
  exec_xml('editor', 'procEditorCall', params, completeGetEmoticons, response_tags);
}

function completeGetEmoticons(ret_obj) {
    var emoticons = ret_obj['emoticons'].split("\n");

    var zone = xGetElementById("popBody");
    var html = "";
    for(var i=0;i<emoticons.length;i++) {
        html += '<img src="./modules/editor/components/emoticon/tpl/images/'+emoticons[i]+'" onclick="insertEmoticon(this);return false;" class="emoticon" />';
    }
    xInnerHtml(zone, html);
    setFixedPopupSize();
    setTimeout(setFixedPopupSize,1000);
}
