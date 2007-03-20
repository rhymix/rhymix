/**
 * popup으로 열렸을 경우 부모창의 위지윅에디터에 select된 멀티미디어 컴포넌트 코드를 체크하여
 * 있으면 가져와서 원하는 곳에 삽입
 **/
var selected_node = null;
function getQuotation() {
    // 부모 위지윅 에디터에서 선택된 영역이 있는지 확인
    if(typeof(opener)=="undefined") return;

    var node = opener.editorPrevNode;
    if(!node || node.nodeName != "DIV") return;

    selected_node = node;

    var use_folder = node.getAttribute("use_folder");
    var folder_opener = node.getAttribute("folder_opener");
    var folder_closer = node.getAttribute("folder_closer");
    var bold = node.getAttribute("bold");
    var color = node.getAttribute("color");
    var margin = node.getAttribute("margin");
    var padding = node.getAttribute("padding");
    var border_style = node.getAttribute("border_style");
    var border_thickness = node.getAttribute("border_thickness");
    var border_color = node.getAttribute("border_color");
    var bg_color = node.getAttribute("bg_color");

    if(use_folder=="Y") xGetElementById("quotation_use").checked = true;
    else xGetElementById("quotation_use").checked = false;
    toggle_folder( xGetElementById("quotation_use") );

    if(bold=="Y") xGetElementById("quotation_bold").checked = true;
    switch(color) {
      case "red" :
          xGetElementById("quotation_color_red").checked = true;
        break;
      case "yellow" :
          xGetElementById("quotation_color_yellow").checked = true;
        break;
      case "green" :
          xGetElementById("quotation_color_green").checked = true;
        break;
      default :
          xGetElementById("quotation_color_blue").checked = true;
        break;
    }

    xGetElementById("quotation_opener").value = folder_opener;
    xGetElementById("quotation_closer").value = folder_closer;
    xGetElementById("quotation_margin").value = margin;
    xGetElementById("quotation_padding").value = padding;

    switch(border_style) {
        case "solid" :
                xGetElementById("border_style_solid").checked = true;
            break;
        case "dotted" :
                xGetElementById("border_style_dotted").checked = true;
            break;
        case "left_solid" :
                xGetElementById("border_style_left_solid").checked = true;
            break;
        case "left_dotted" :
                xGetElementById("border_style_left_dotted").checked = true;
            break;
        default : 
                xGetElementById("border_style_none").checked = true;
            break;
    }

    xGetElementById("border_thickness").value = border_thickness;

    select_color('border', border_color); 
    select_color('bg', bg_color); 
}

/* 추가 버튼 클릭시 부모창의 위지윅 에디터에 인용구 추가 */
function insertQuotation() {
    if(typeof(opener)=="undefined") return;

    var use_folder = "N";
    if(xGetElementById("quotation_use").checked) use_folder = "Y";

    var folder_opener = xGetElementById("quotation_opener").value;
    var folder_closer = xGetElementById("quotation_closer").value;
    if(!folder_opener||!folder_closer) use_folder = "N";

    var bold = "N";
    if(xGetElementById("quotation_bold").checked) bold = "Y";
    var color = "blue";
    if(xGetElementById("quotation_color_red").checked) color = "red";
    if(xGetElementById("quotation_color_yellow").checked) color = "yellow";
    if(xGetElementById("quotation_color_green").checked) color = "green";

    var margin = parseInt(xGetElementById("quotation_margin").value,10);

    var padding = parseInt(xGetElementById("quotation_padding").value,10);

    var border_style = "solid";
    if(xGetElementById("border_style_none").checked) border_style = "none";
    if(xGetElementById("border_style_solid").checked) border_style = "solid";
    if(xGetElementById("border_style_dotted").checked) border_style = "dotted";
    if(xGetElementById("border_style_left_solid").checked) border_style = "left_solid";
    if(xGetElementById("border_style_left_dotted").checked) border_style = "left_dotted";

    var border_thickness = parseInt(xGetElementById("border_thickness").value,10);

    var border_color = xGetElementById("border_color_input").value;

    var bg_color = xGetElementById("bg_color_input").value;

    var content = "";
    if(selected_node) content = xInnerHtml(selected_node);
    else content = opener.editorGetSelectedHtml(opener.editorPrevSrl);

    var style = "margin:"+margin+"px; padding:"+padding+"px; background-color:#"+bg_color+";";
    switch(border_style) {
        case "solid" :
                style += "border:"+border_thickness+"px solid #"+border_color+";";
            break;
        case "dotted" :
                style += "border:"+border_thickness+"px dotted #"+border_color+";";
            break;
        case "left_solid" :
                style += "border-left:"+border_thickness+"px solid #"+border_color+";";
            break;
        case "left_dotted" :
                style += "border-elft:"+border_thickness+"px dotted #"+border_color+";";
            break;
    }

    if(!content) content = "&nbsp;";

    var text = "\n<div editor_component=\"quotation\" use_folder=\""+use_folder+"\" folder_opener=\""+folder_opener+"\" folder_closer=\""+folder_closer+"\" bold=\""+bold+"\" color=\""+color+"\" margin=\""+margin+"\" padding=\""+padding+"\" border_style=\""+border_style+"\" border_thickness=\""+border_thickness+"\" border_color=\""+border_color+"\" bg_color=\""+bg_color+"\" style=\""+style+"\">"+content+"</div>\n";

    if(selected_node) {
        selected_node.setAttribute("use_folder", use_folder);
        selected_node.setAttribute("folder_opener", folder_opener);
        selected_node.setAttribute("folder_closer", folder_closer);
        selected_node.setAttribute("bold", bold);
        selected_node.setAttribute("color", color);
        selected_node.setAttribute("margin", margin);
        selected_node.setAttribute("padding", padding);
        selected_node.setAttribute("border_style", border_style);
        selected_node.setAttribute("border_thickness", border_thickness);
        selected_node.setAttribute("border_color", border_color);
        selected_node.setAttribute("bg_color", bg_color);
        selected_node.setAttribute("style", style);

        if(selected_node.outHTML) selected_node.outHTML = text;

        opener.editorFocus(opener.editorPrevSrl);

    } else {

        opener.editorFocus(opener.editorPrevSrl);
        var iframe_obj = opener.editorGetIFrame(opener.editorPrevSrl)
        opener.editorReplaceHTML(iframe_obj, text);
        opener.editorFocus(opener.editorPrevSrl);
    }

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
