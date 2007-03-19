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

    if(!url) {
      window.close();
      return;
    }

    var text = "<img src=\""+url+"\" border=\""+border+"\" plugin=\"image_link\" ";
    if(alt) text+= " alt=\""+alt+"\"";
    if(align) text+= " align=\""+align+"\" ";
    text+= " />";

    opener.editorFocus(opener.editorPrevSrl);

    var iframe_obj = opener.editorGetIFrame(opener.editorPrevSrl)

    opener.editorReplaceHTML(iframe_obj, text);
    opener.editorFocus(opener.editorPrevSrl);

    window.close();
}
