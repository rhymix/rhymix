/**
 * popup으로 열렸을 경우 부모창의 위지윅에디터에 select된 이미지가 있는지 체크하여
 * 있으면 가져와서 원하는 곳에 삽입
 **/
function getImage() {
    // 부모 위지윅 에디터에서 선택된 영역이 있는지 확인
    if(typeof(opener)=="undefined") return;

    // url이 미리 입력되어 있을 경우 scale구해줌
    if(xGetElementById("image_url").value) {
        getImageScale();
        return;
    }

    // 부모 위지윅 에디터에서 선택된 영역이 있으면 처리
    var node = opener.editorPrevNode;
    if(!node || node.nodeName != "IMG") {
        return;
    }
    var src = node.getAttribute("src");
    var border = node.getAttribute("border");
    var align = node.getAttribute("align");
    var alt = node.getAttribute("alt");
    var width = node.getAttribute("width");
    var height = node.getAttribute("height");

    xGetElementById("image_url").value = src;
    xGetElementById("image_alt").value = alt;

    switch(align) {
        case 'left' : xGetElementById("align_left").checked = true; break;
        case 'middle' : xGetElementById("align_middle").checked = true; break;
        case 'right' : xGetElementById("align_right").checked = true; break;
        default : xGetElementById("align_normal").checked = true; break;
    }

    xGetElementById("image_border").value = border;

    xGetElementById("width").value = width;
    xGetElementById("height").value = height;

}

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

    url = url.replace(request_uri,'');
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

xAddEventListener(window, "load", getImage);
