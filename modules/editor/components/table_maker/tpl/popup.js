/**
 * popup으로 열렸을 경우 부모창의 위지윅에디터에 select된 멀티미디어 컴포넌트 코드를 체크하여
 * 있으면 가져와서 원하는 곳에 삽입
 **/
var selected_node = null;
function getTable() {
    // 부모 위지윅 에디터에서 선택된 영역이 있는지 확인
    if(typeof(opener)=="undefined") return;

    var node = opener.editorPrevNode;
    selected_node = node;

    // 선택된 객체가 없으면 테이블 새로 추가
    if(!selected_node) {
      doSelectOption('table');
    } else {
      doSelectOption('cell');
    }

    setFixedPopupSize();
}

/* 테이블, 셀 선택 옵션의 처리 */
function doSelectOption(type) {

    // 셀 변경 
    if(selected_node && type == "cell") {
      xGetElementById("table_option").style.display = "block";
      xGetElementById("cell_attribute").style.display = "block";

      var cell_width = selected_node.style.width.replace(/(px|\%)$/,'');
      var cell_width_unit = selected_node.style.width.replace(/^([0-9]+)/,'');
      var cell_height = selected_node.style.height.replace(/px$/,'');

      var border_color = selected_node.style.borderColor.replace(/^#/,'');
      if(border_color.indexOf('rgb')>-1) {
        var tmp_color = border_color.replace(/([a-z\(\) ]*)/ig,'').split(',');
        border_color = xHex(tmp_color[0], 2, '')+xHex(tmp_color[1], 2, '')+xHex(tmp_color[2], 2, '');
      }

      var bg_color = selected_node.style.backgroundColor.replace(/^#/,'');
      if(bg_color.indexOf('rgb')>-1) {
        var tmp_color = bg_color.replace(/([a-z\(\) ]*)/ig,'').split(',');
        bg_color = xHex(tmp_color[0], 2, '')+xHex(tmp_color[1], 2, '')+xHex(tmp_color[2], 2, '');
      }
      if(!bg_color) bg_color = "FFFFFF";

      xGetElementById("cell_width").value = cell_width?cell_width:0;
      if(cell_width_unit=="px") xGetElementById("cell_width_unit_pixel").checked = "true";
      else xGetElementById("cell_width_unit_percent").value = "true";
      xGetElementById("cell_height").value = cell_height?cell_height:0;
      
      xGetElementById("border_color_input").value = border_color;
      manual_select_color("border", xGetElementById("border_color_input"))
      xGetElementById("bg_color_input").value = bg_color;
      manual_select_color("bg", xGetElementById("bg_color_input"))

      xGetElementById("table_attribute").style.display = "none";
      xGetElementById("cell_attribute").style.display = "block";
      xGetElementById("cell_attribute_select").checked = true;
      xGetElementById("border_color_area").style.display = "none";
      xGetElementById("bg_color_area").style.display = "block";

    // 테이블 변경 
    } else {
      var table_obj = xParent(selected_node);
      while(table_obj && table_obj.nodeName != "TABLE") { table_obj = xParent(table_obj); }
      if(!table_obj) xGetElementById("col_row_area").style.display = "block";
      else {
        xGetElementById("col_row_area").style.display = "none";

        var width = table_obj.width.replace(/\%/,'');
        var width_unit = table_obj.width.replace(/^([0-9]+)/,'');
        if(!width_unit) xGetElementById("width_unit_pixel").checked = "true";
        else xGetElementById("width_unit_percent").value = "true";

        var border = table_obj.style.borderLeftWidth.replace(/px$/,'');
        if(!border) border = 0;
        var inner_border = table_obj.getAttribute("border");
        if(!inner_border) inner_border = 0;
        var cellspacing = table_obj.getAttribute("cellspacing");
        if(!cellspacing) cellspacing = 0;
        var cellpadding = table_obj.getAttribute("cellpadding");
        if(!cellpadding) cellpadding = 1;

        var border_color = table_obj.style.borderColor.replace(/^#/,'');
        if(border_color.indexOf('rgb')>-1) {
          var tmp_color = border_color.replace(/([a-z\(\) ]*)/ig,'').split(',');
          border_color = xHex(tmp_color[0], 2, '')+xHex(tmp_color[1], 2, '')+xHex(tmp_color[2], 2, '');
        }

        var bg_color = table_obj.style.backgroundColor.replace(/^#/,'');
        if(bg_color.indexOf('rgb')>-1) {
          var tmp_color = bg_color.replace(/([a-z\(\) ]*)/ig,'').split(',');
          bg_color = xHex(tmp_color[0], 2, '')+xHex(tmp_color[1], 2, '')+xHex(tmp_color[2], 2, '');
        }
      
        xGetElementById("border_color_input").value = border_color;
        manual_select_color("border", xGetElementById("border_color_input"))
        xGetElementById("bg_color_input").value = bg_color;
        manual_select_color("bg", xGetElementById("bg_color_input"))

        xGetElementById("width").value = width;
        xGetElementById("border").value = border;
        xGetElementById("inner_border").value = inner_border;
        xGetElementById("cellspacing").value = cellspacing;
        xGetElementById("cellpadding").value = cellpadding
      }
      xGetElementById("table_attribute").style.display = "block";
      xGetElementById("cell_attribute").style.display = "none";
      xGetElementById("table_attribute_select").checked = true;
      xGetElementById("border_color_area").style.display = "block";
      xGetElementById("bg_color_area").style.display = "block";
    }

    setFixedPopupSize();
}

/* 추가 버튼 클릭시 부모창의 위지윅 에디터에 인용구 추가 */
function insertTable() {
    if(typeof(opener)=="undefined") return;

    var table_obj = null;

    if(selected_node) {
      table_obj = xParent(selected_node);
      while(table_obj && table_obj.nodeName != "TABLE") { table_obj = xParent(table_obj); }
    }

    // 테이블 생성일 경우
    if(xGetElementById("table_attribute_select").checked && !table_obj) {
      var cols_count = parseInt(xGetElementById("cols_count").value,10);
      if(!cols_count) cols_count = 1;

      var rows_count = parseInt(xGetElementById("rows_count").value,10);
      if(!rows_count) rows_count = 1;

      var width = parseInt(xGetElementById("width").value,10);
      var width_unit = "%";
      if(xGetElementById("width_unit_pixel").checked) width_unit = "";

      var border = parseInt(xGetElementById("border").value,10);
      var inner_border = parseInt(xGetElementById("inner_border").value,10);
      var cellspacing = parseInt(xGetElementById("cellspacing").value,10);
      var cellpadding = parseInt(xGetElementById("cellpadding").value,10);
      var border_color = xGetElementById("border_color_input").value;
      if(border_color.length!=6) border_color = "000000";

      var bg_color = xGetElementById("bg_color_input").value;
      if(bg_color.length!=6) bg_color = "FFFFFF";

      var text = "";
      text += "<table width=\""+width+width_unit+"\" border=\""+inner_border+"\" cellspacing=\""+cellspacing+"\" cellpadding=\""+cellpadding+"\" ";
      if(border>0) text += " style=\"border:"+border+"px solid #"+border_color+";background-color:#"+bg_color+"\" ";
      text +=">";

      for(var row=0; row<rows_count;row++) {
          text += "<tr valign=\"top\">";
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

    // 테이블 수정일 경우
    } else if(xGetElementById("table_attribute_select").checked && table_obj) {
      var width = parseInt(xGetElementById("width").value,10);
      var width_unit = "%";
      if(xGetElementById("width_unit_pixel").checked) width_unit = "px";
      var border = parseInt(xGetElementById("border").value,10);
      var inner_border = parseInt(xGetElementById("inner_border").value,10);
      var cellspacing = parseInt(xGetElementById("cellspacing").value,10);
      var cellpadding = parseInt(xGetElementById("cellpadding").value,10);
      var border_color = xGetElementById("border_color_input").value;
      if(border_color.length!=6) border_color = "000000";

      var bg_color = xGetElementById("bg_color_input").value;
      if(bg_color.length!=6) bg_color = "FFFFFF";

      table_obj.style.width = width+width_unit;
      if(width_unit=="px") table_obj.setAttribute("width", width);
      else table_obj.setAttribute("width", width+width_unit);
      table_obj.setAttribute("border", inner_border);
      table_obj.setAttribute("cellspacing", cellspacing);
      table_obj.setAttribute("cellpadding", cellpadding);
      table_obj.style.border = border+"px solid #"+border_color;
      table_obj.style.backgroundColor = "#"+bg_color;

    // cell의 수정일 경우
    } if(xGetElementById("cell_attribute_select").checked && selected_node) {
      var cell_width = parseInt(xGetElementById("cell_width").value,10);
      var cell_width_unit = "%";
      if(xGetElementById("cell_width_unit_pixel").checked) cell_width_unit = "px";
      var cell_height = parseInt(xGetElementById("cell_height").value,10);

      var bg_color = xGetElementById("bg_color_input").value;
      if(bg_color.length!=6) bg_color = "FFFFFF";

      selected_node.style.width = cell_width+cell_width_unit;
      selected_node.style.height = cell_height+"px";
      selected_node.style.backgroundColor = "#"+bg_color;
    }

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

