/**
 * popup으로 열렸을 경우 부모창의 위지윅에디터에 select된 멀티미디어 컴포넌트 코드를 체크하여
 * 있으면 가져와서 원하는 곳에 삽입
 **/
var selected_node = null;
function getTable() {
    // 부모 위지윅 에디터에서 선택된 영역이 있는지 확인
    if(typeof(opener)=="undefined") return;

}

/* 추가 버튼 클릭시 부모창의 위지윅 에디터에 인용구 추가 */
function insertTable() {
    if(typeof(opener)=="undefined") return;

    var cols_count = parseInt(xGetElementById("cols_count").value,10);
    if(!cols_count) cols_count = 1;

    var rows_count = parseInt(xGetElementById("rows_count").value,10);
    if(!rows_count) rows_count = 1;

    var width = parseInt(xGetElementById("width").value,10);

    var border = parseInt(xGetElementById("border").value,10);

    var inner_border = parseInt(xGetElementById("inner_border").value,10);

    var cellspacing = parseInt(xGetElementById("cellspacing").value,10);

    var cellpadding = parseInt(xGetElementById("cellpadding").value,10);

    var border_color = xGetElementById("border_color_input").value;
    if(border_color.length!=6) border_color = "000000";

    var bg_color = xGetElementById("bg_color_input").value;
    if(bg_color.length!=6) bg_color = "FFFFFF";

    var text = "\n<table width=\""+width+"\" border=\""+inner_border+"\" cellspacing=\""+cellspacing+"\" cellpadding=\""+cellpadding+"\" ";
    text += "style=\"border:"+border+"px solid #"+border_color+";background-color:#"+bg_color+"\"";
    text +=">";

    for(var row=0; row<rows_count;row++) {
        text += "<tr>";
        for(var col=0; col<cols_count;col++) {
            text += "<td>&nbsp;</td>";
        }
        text += "</tr>";
    }
    text += "</table>\n<br />";

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

xAddEventListener(window, "load", getTable);
