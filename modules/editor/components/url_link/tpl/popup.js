/**
 * popup으로 열렸을 경우 부모창의 위지윅에디터에 select된 block이 있는지 체크하여
 * 있으면 가져와서 원하는 곳에 삽입
 **/
function getText() {
    // 부모 위지윅 에디터에서 선택된 영역이 있는지 확인
    if(typeof(opener)=="undefined") return;
    var text = opener.editorGetSelectedHtml(opener.editorPrevSrl);

    // 선택된 영역이 A태그인지 확인
    if(text) {
      var node = opener.editorGetSelectedNode(opener.editorPrevSrl);
      if(node.nodeName == "A") {
        var url = node.getAttribute("HREF");

        var onclick_str = "";
        if(xIE4Up) {
            onclick_str = node.outerHTML;
        } else {
            if(node.getAttribute("onclick")) onclick_str = node.getAttribute("onclick");
        }

        var className = "";
        if(typeof(node.className)) className = node.className;
        else className = node.getAttribute("class");
        var open_window = false;

        if(onclick_str) {
          open_window = true;

          var s_s = "window.open('";
          var p_s = onclick_str.indexOf(s_s);
          url = onclick_str.substr(p_s+s_s.length);

          var e_s = "')";
          var p_e = url.indexOf(e_s);
          url = url.substr(0, p_e);

        }

        var bold = false;
        var color = "";

        if(className) {
          if(className.indexOf("bold")>-1) bold = true;

          if(className.indexOf("blue")>0) color = "color_blue";
          else if(className.indexOf("red")>0) color = "color_red";
          else if(className.indexOf("yellow")>0) color = "color_yellow";
          else if(className.indexOf("green")>0) color = "color_green";
        }

        var fo_obj = xGetElementById("fo_component");

        fo_obj.text.value = xInnerHtml(node);
        fo_obj.url.value = url;
        if(open_window) fo_obj.open_window.checked = true;
        if(bold) fo_obj.bold.checked = true;
        if(color) xGetElementById(color).checked = true;

        return;
      } 
    }

    // 기본 설정 
    var fo_obj = xGetElementById("fo_component");
    if(fo_obj.text.value) return;
    fo_obj.text.value = text;
    self.focus();
}

/**
 * 부모창의 위지윅에디터에 데이터를 삽입
 **/
function setText() {
    if(typeof(opener)=="undefined") return;

    var fo_obj = xGetElementById("fo_component");

    var text = fo_obj.text.value;
    var url = fo_obj.url.value;
    var open_window = false;
    var bold = false;
    var link_class = "";

    if(!text) {
        window.close();
        return;
    }

    if(!url) url = "#";

    if(fo_obj.open_window.checked) open_window = true;
    if(fo_obj.bold.checked) bold= true;
    if(xGetElementById("color_blue").checked) link_class = "editor_blue_text";
    else if(xGetElementById("color_red").checked) link_class = "editor_red_text";
    else if(xGetElementById("color_yellow").checked) link_class = "editor_yellow_text";
    else if(xGetElementById("color_green").checked) link_class = "editor_green_text";

    var link = "";
    if(open_window) {
        link = "<a href=\"#\" onclick=\"window.open('"+url+"');return false;\" ";
    } else {
        link = "<a href=\""+url+"\" ";
    }
    
    if(bold || link_class) {
        var class_name = "";
        if(bold) class_name = "bold";
        if(link_class) class_name += " "+link_class;
        link += " class=\""+class_name+"\" ";
    }

    link += ">"+text+"</a>";

    var iframe_obj = opener.editorGetIFrame(opener.editorPrevSrl)
    opener.editorReplaceHTML(iframe_obj, link);

    opener.focus();
    window.close();
}

xAddEventListener(window, "load", getText);
