/**
 * popup으로 열렸을 경우 부모창의 위지윅에디터에 select된 멀티미디어 컴포넌트 코드를 체크하여
 * 있으면 가져와서 원하는 곳에 삽입
 **/
function getQuotation() {
  return;
    // 부모 위지윅 에디터에서 선택된 영역이 있는지 확인
    if(typeof(opener)=="undefined") return;

    var node = opener.editorPrevNode;
    if(!node || node.nodeName != "DIV") return;

    var url = node.getAttribute("src");
    var caption = xInnerHtml(node);
    var width = node.getAttribute("width");
    var height = node.getAttribute("height");
    var auto_start = node.getAttribute("auto_start");

    xGetElementById("multimedia_url").value = url;
    xGetElementById("multimedia_caption").value = caption;
    xGetElementById("multimedia_width").value = width;
    xGetElementById("multimedia_height").value = height;
    if(auto_start=="true") xGetElementById("multimedia_auto_start").checked = true;

}

function insertQuotation(obj) {
    if(typeof(opener)=="undefined") return;

    var url = xGetElementById("_url").value;

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

/* 색상 클릭시 */
function select_color(type, code) {
  xGetElementById(type+"_preview_color").style.backgroundColor = "#"+code;
  xGetElementById(type+"_color_input").value = code;

  if(type=="border") {
    xGetElementById("border_style_solid_icon").style.backgroundColor = "#"+code;
    xGetElementById("border_style_dotted_icon").style.backgroundColor = "#"+code;
    xGetElementById("border_style_left_solid_icon").style.backgroundColor = "#"+code;
    xGetElementById("border_style_left_dotted_icon").style.backgroundColor = "#"+code;
  }
}

/* 수동 색상 변경시 */
function manual_select_color(type, obj) {
  if(obj.value.length!=6) return;
  code = obj.value;
  xGetElementById(type+"_preview_color").style.backgroundColor = "#"+code;

  if(type=="border") {
    xGetElementById("border_style_solid_icon").style.backgroundColor = "#"+code;
    xGetElementById("border_style_dotted_icon").style.backgroundColor = "#"+code;
    xGetElementById("border_style_left_solid_icon").style.backgroundColor = "#"+code;
    xGetElementById("border_style_left_dotted_icon").style.backgroundColor = "#"+code;
  }
}

/* 색상표를 출력 */
function printColor(type, blank_img_src) {
  var colorTable = new Array('22','44','66','88','AA','CC','EE');
  var html = "";

  for(var i=0;i<8;i+=1) html += printColorBlock(type, i.toString(16)+i.toString(16)+i.toString(16)+i.toString(16)+i.toString(16)+i.toString(16), blank_img_src);

  for(var i=0; i<colorTable.length; i+=3) {
    for(var j=0; j<colorTable.length; j+=2) {
      for(var k=0; k<colorTable.length; k++) {
        var code = colorTable[i] + colorTable[j] + colorTable[k];
        html += printColorBlock(type, code, blank_img_src);
      }
    }
  }

  for(var i=8;i<16;i+=1) html += printColorBlock(type, i.toString(16)+i.toString(16)+i.toString(16)+i.toString(16)+i.toString(16)+i.toString(16), blank_img_src);

  document.write(html);
}

/* 개별 색상 block 출력 함수 */
function printColorBlock(type, code, blank_img_src) {
  if(type=="bg") {
    return "<div style=\"float:left;background-color:#"+code+"\"><img src=\""+blank_img_src+"\" class=\"color_icon\" onmouseover=\"this.className='color_icon_over'\" onmouseout=\"this.className='color_icon'\" onclick=\"select_color('"+type+"','"+code+"')\" alt=\"color\" \/><\/div>";
  } else {
    return "<div style=\"float:left;background-color:#"+code+"\"><img src=\""+blank_img_src+"\" class=\"color_icon\" onmouseover=\"this.className='color_icon_over'\" onmouseout=\"this.className='color_icon'\" onclick=\"select_color('"+type+"','"+code+"')\" alt=\"color\" \/><\/div>";
  }
}

/* 폴더 여닫기 기능 toggle */
function toggle_folder(obj) {
  if(obj.checked) xGetElementById("folder_area").style.display = "block";
  else xGetElementById("folder_area").style.display = "none";
  setFixedPopupSize();
}

xAddEventListener(window, "load", getQuotation);
