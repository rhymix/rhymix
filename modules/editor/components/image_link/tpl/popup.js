function getImageScale() {
    var url = xGetElementById("image_url").value;
    if(!url) return;

    var img = new Image();
    img.src = url;

    xGetElementById("width").value = img.width;
    xGetElementById("height").value = img.height;
    
}
function insertImage(obj) {
    if(typeof(opener)=="undefined") return;

    var url = xGetElementById("image_url").value;
    var alt = xGetElementById("image_alt").value;
    var align = "";
    if(xGetElementById("align_normal").checked==true) align = "";
    else if(xGetElementById("align_left").checked==true) align = "left";
    else if(xGetElementById("align_middle").checked==true) align = "middle";
    else if(xGetElementById("align_right").checked==true) align = "right";
    var border = parseInt(xGetElementById("image_border").value,10);

    var width = xGetElementById("width").value;
    var height = xGetElementById("height").value;

    if(!url) {
      window.close();
      return;
    }

    var text = "<img editor_component=\"image_link\" src=\""+url+"\" border=\""+border+"\" ";
    if(alt) text+= " alt=\""+alt+"\"";
    if(align) text+= " align=\""+align+"\" ";
    if(width) text+= " width=\""+width+"\" ";
    if(height) text+= " height=\""+height+"\" ";
    text+= " />";

    opener.editorFocus(opener.editorPrevSrl);

    var iframe_obj = opener.editorGetIFrame(opener.editorPrevSrl)

    opener.editorReplaceHTML(iframe_obj, text);
    opener.editorFocus(opener.editorPrevSrl);

    window.close();
}
