function insertMultimedia(obj) {
    if(typeof(opener)=="undefined") return;

    var url = xGetElementById("multimedia_url").value;

    var caption = xGetElementById("multimedia_caption").value;

    var width = xGetElementById("multimedia_width").value;
    if(!width) width = 640;

    var height = xGetElementById("multimedia_height").value;
    if(!height) height= 480;

    var auto_start = "false";
    if(xGetElementById("multimedia_auto_start").checked) auto_start = "true";

    if(!url) {
      window.close();
      return;
    }

    var text = "<div editor_component=\"multimedia_link\" class=\"editor_multimedia\" src=\""+url+"\" width=\""+width+"\" height=\""+height+"\" style=\"width:"+width+"px;height:"+height+"px;\" auto_start=\""+auto_start+"\">"+caption+"</div>";

    opener.editorFocus(opener.editorPrevSrl);

    var iframe_obj = opener.editorGetIFrame(opener.editorPrevSrl)

    opener.editorReplaceHTML(iframe_obj, text);
    opener.editorFocus(opener.editorPrevSrl);

    window.close();
}
