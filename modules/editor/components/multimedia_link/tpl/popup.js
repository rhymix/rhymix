/**
 * popup으로 열렸을 경우 부모창의 위지윅에디터에 select된 멀티미디어 컴포넌트 코드를 체크하여
 * 있으면 가져와서 원하는 곳에 삽입
 **/
function getMultimedia() {
    // 부모 위지윅 에디터에서 선택된 영역이 있는지 확인
    if(typeof(opener)=="undefined") return;

    var node = opener.editorPrevNode;
    if(!node || node.nodeName != "IMG") return;

    var url = node.getAttribute("multimedia_src");
    var caption = node.getAttribute("alt");
    var width = xWidth(node);
    var height = xHeight(node);
    var auto_start = node.getAttribute("auto_start");

    xGetElementById("multimedia_url").value = url;
    xGetElementById("multimedia_caption").value = caption;
    xGetElementById("multimedia_width").value = width-4;
    xGetElementById("multimedia_height").value = height-4;
    if(auto_start=="true") xGetElementById("multimedia_auto_start").checked = true;

}

function insertMultimedia(obj) {
    if(typeof(opener)=="undefined") return;

    var url = xGetElementById("multimedia_url").value;
    url = url.replace(request_uri,'');

    var caption = xGetElementById("multimedia_caption").value;

    var width = xGetElementById("multimedia_width").value;
    if(!width) width = 400;

    var height = xGetElementById("multimedia_height").value;
    if(!height) height= 400;

    var auto_start = "false";
    if(xGetElementById("multimedia_auto_start").checked) auto_start = "true";

    if(!url) {
      window.close();
      return;
    }

    var text = "<img src=\"./common/tpl/images/blank.gif\" editor_component=\"multimedia_link\" multimedia_src=\""+url+"\" width=\""+width+"\" height=\""+height+"\" style=\"display:block;width:"+width+"px;height:"+height+"px;border:2px dotted #4371B9;background:url(./modules/editor/components/multimedia_link/tpl/multimedia_link_component.gif) no-repeat center;\" auto_start=\""+auto_start+"\" alt=\""+caption+"\" />";

    opener.editorFocus(opener.editorPrevSrl);

    var iframe_obj = opener.editorGetIFrame(opener.editorPrevSrl)

    opener.editorReplaceHTML(iframe_obj, text);
    opener.editorFocus(opener.editorPrevSrl);

    window.close();
}

xAddEventListener(window, "load", getMultimedia);
