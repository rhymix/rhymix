/**
 * popup으로 열렸을 경우 부모창의 위지윅에디터에 select된 이미지가 있는지 체크하여
 * 있으면 가져와서 원하는 곳에 삽입
 **/

var orig_width = 0;
var orig_height = 0;
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
    var border = node.style.borderWidth ? 
        node.style.borderWidth.match("[0-9]+") : node.getAttribute("border");
    var align = node.style.cssFloat ?
        node.style.cssFloat : node.style.styleFloat;
    if(!align) align = node.style.verticalAlign?
        node.style.verticalAlign : node.getAttribute("align");
    var margin = node.style.margin ? 
        node.style.margin.match("[0-9]+") : node.getAttribute("margin");
    var alt = node.getAttribute("alt");
    var width = xWidth(node);
    var height = xHeight(node);
    orig_width = width;
    orig_height = height;
    var link_url = node.getAttribute("link_url");
    var open_window = node.getAttribute("open_window");

    xGetElementById("image_url").value = src;
    xGetElementById("image_alt").value = alt;

    if(link_url) {
        link_url = link_url.replace(/<([^>]*)>/ig,'').replace(/&lt;/ig,'<').replace(/&gt;/ig,'>').replace(/&amp;/ig,'&');
        xGetElementById('link_url').value = link_url;
    }
    if(open_window == 'Y') xGetElementById('open_window').checked = "true";

    switch(align) {
        case 'left' : xGetElementById("align_left").checked = true; break;
        case 'middle' : xGetElementById("align_middle").checked = true; break;
        case 'right' : xGetElementById("align_right").checked = true; break;
        default : xGetElementById("align_normal").checked = true; break;
    }

    if(margin) {
        xGetElementById('image_margin').value = margin;
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

    orig_width = img.width;
    orig_height = img.height;
}
function insertImage(obj) {
    if(typeof(opener)=="undefined") return;

    var link_url = xGetElementById('link_url').value;
    if(link_url) link_url = link_url.replace(/&/ig,'&amp;').replace(/</ig,'&lt;').replace(/>/ig,'&gt;');
    var open_window = 'N';
    if(xGetElementById('open_window').checked) open_window = 'Y';

    var url = xGetElementById("image_url").value;
    var alt = xGetElementById("image_alt").value;
    var align = "";
    if(xGetElementById("align_normal").checked==true) align = "";
    else if(xGetElementById("align_left").checked==true) align = "float: left";
    else if(xGetElementById("align_middle").checked==true) align = "vertical-align: middle";
    else if(xGetElementById("align_right").checked==true) align = "float: right";
    var border = parseInt(xGetElementById("image_border").value,10);
    var margin = parseInt(xGetElementById("image_margin").value,10);

    var width = xGetElementById("width").value;
    var height = xGetElementById("height").value;

    if(!url) {
      window.close();
      return;
    }

    url = url.replace(request_uri,'');
    var text = "<img editor_component=\"image_link\" src=\""+url+"\"";
    if(alt) text+= " alt=\""+alt+"\"";
    if(width) text+= " width=\""+width+"\"";
    if(height) text+= " height=\""+height+"\"";
    if(link_url) text+= " link_url=\""+link_url+"\"";
    if(open_window=='Y') text+= " open_window=\"Y\"";
    if(align || border){
        text+= " style=\"";
        if(align) text+= align+"; ";
        if(border) text+= "border: solid "+border+"px; ";
        if(margin) text+= "margin: "+margin+"px; ";
        text+= "\" ";
    }
    if(border) text+= " border=\""+border+"\""
    if(margin) text+= " margin=\""+margin+"\""
    text+= " />";

    opener.editorFocus(opener.editorPrevSrl);

    var iframe_obj = opener.editorGetIFrame(opener.editorPrevSrl)

    opener.editorReplaceHTML(iframe_obj, text);
    opener.editorFocus(opener.editorPrevSrl);

    window.close();
}

xAddEventListener(window, "load", getImage);

function setScale(type) {
    switch(type) {
        case 'width' :
                if(!orig_height) return;
                var n_width = xGetElementById('width').value;
                var p = n_width/orig_width;
                var n_height = parseInt(orig_height * p,10);
                xGetElementById('height').value = n_height;
            break;
        case 'height' :
                if(!orig_width) return;
                var n_height = xGetElementById('height').value;
                var p = n_height/orig_height;
                var n_width = parseInt(orig_width * p,10);
                xGetElementById('width').value = n_width;
            break;
    }

}
