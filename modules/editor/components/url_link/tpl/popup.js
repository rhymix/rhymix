/**
 * popup으로 열렸을 경우 부모창의 위지윅에디터에 select된 block이 있는지 체크하여
 * 있으면 가져와서 원하는 곳에 삽입
 **/
function getText() {
    var node = opener.editorPrevNode;
    if(!node) {
        var fo_obj = xGetElementById("fo_component");
        var text = opener.editorGetSelectedHtml(opener.editorPrevSrl);
        if(text==undefined) text = "";
        text = text.replace(/<([^>]*)>/ig,'').replace(/&lt;/ig,'<').replace(/&gt;/ig,'>').replace(/&amp;/ig,'&');
        fo_obj.text.value = text;
        return;
    }

    if(node.nodeName == "A") {
        var url = node.getAttribute("HREF");
        var text = node.text.replace(/&lt;/ig,'<').replace(/&gt;/ig,'>').replace(/&amp;/ig,'&');

        var open_window = false;
        var bold = false;
        var color = "";
        var className = node.className;

        var selectedHtml = opener.editorGetSelectedHtml(opener.editorPrevSrl);
        if(selectedHtml.indexOf("window.open")>0) open_window = true;

        if(className) {
          if(className.indexOf("bold")>-1) bold = true;

          if(className.indexOf("blue")>0) color = "color_blue";
          else if(className.indexOf("red")>0) color = "color_red";
          else if(className.indexOf("yellow")>0) color = "color_yellow";
          else if(className.indexOf("green")>0) color = "color_green";
        }

        var fo_obj = xGetElementById("fo_component");

        fo_obj.text.value = text;
        fo_obj.url.value = url.replace('&amp;','&');
        if(open_window) fo_obj.open_window.checked = true;
        if(bold) fo_obj.bold.checked = true;
        if(color) xGetElementById(color).checked = true;

        return;
    } else if(node.nodeName == "IMG") {
    } else {
        var fo_obj = xGetElementById("fo_component");
        var text = opener.editorGetSelectedHtml(opener.editorPrevSrl);
        fo_obj.text.value = text.replace(/<([^>]*)>/ig,'').replace(/&lt;/ig,'<').replace(/&gt;/ig,'>').replace(/&amp;/ig,'&');
    }
}

/**
 * 부모창의 위지윅에디터에 데이터를 삽입
 **/
function setText() {
    if(typeof(opener)=="undefined") return;

    var fo_obj = xGetElementById("fo_component");

    var text = fo_obj.text.value;
    text.replace(/&/ig,'&amp;').replace(/</ig,'&lt;').replace(/>/ig,'&gt;');
    var url = fo_obj.url.value;
    url = url.replace(/&/ig,'&amp;');
    var open_window = false;
    var bold = false;
    var link_class = "";
    var link = "";

    if(!text) {
        window.close();
        return;
    }

    if(url) {

        if(fo_obj.open_window.checked) open_window = true;
        if(fo_obj.bold.checked) bold= true;
        if(xGetElementById("color_blue").checked) link_class = "editor_blue_text";
        else if(xGetElementById("color_red").checked) link_class = "editor_red_text";
        else if(xGetElementById("color_yellow").checked) link_class = "editor_yellow_text";
        else if(xGetElementById("color_green").checked) link_class = "editor_green_text";
        else link_class = "";

        link = "<a href=\""+url+"\" ";
        if(open_window) link += "onclick=\"window.open(this.href);return false;\" ";
        
        if(bold || link_class) {
            var class_name = "";
            if(bold) class_name = "bold";
            if(link_class) class_name += " "+link_class;
            link += " class=\""+class_name+"\" ";
        }

        link += ">"+text+"</a>";
    } else {
        link = text;
    }

    var iframe_obj = opener.editorGetIFrame(opener.editorPrevSrl)
    opener.editorReplaceHTML(iframe_obj, link);

    opener.focus();
    window.close();
}

xAddEventListener(window, "load", getText);
