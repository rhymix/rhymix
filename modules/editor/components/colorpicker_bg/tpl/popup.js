var color_list = new Array('000000','993300','333300','003300','003366','000080','333399','333333','800000','FF6600','808000','008000','008080','0000FF','666699','808080','FF0000','FF9900','99CC00','339966','33CCCC','3366FF','800080','969696','FF00FF','FFCC00','FFFF00','00FF00','00FFFF','00CCFF','993366','c0c0c0','FF99CC','FFCC99','FFFF99','CCFFCC','CCFFFF','99CCFF','CC99FF','FFFFFF');

/* 부모창의 위지윅 에디터의 선택된 영역의 글자색을 변경 */
function setColor(color) {
  opener.editorFocus(opener.editorPrevSrl);
  opener.editorSetBackColor("#"+color);
  opener.editorFocus(opener.editorPrevSrl);
  self.close();
}

/* 색상표를 출력 */
function printColor(blank_img_src) {
  var html = "";
  for(var i=0;i<color_list.length;i++) {
    html += printColorBlock(color_list[i], blank_img_src);
  }
  document.write(html);
}

/* 개별 색상 block 출력 함수 */
function printColorBlock(code, blank_img_src) {
    return "<div style=\"float:left;background-color:#"+code+"\"><img src=\""+blank_img_src+"\" class=\"color_icon\" onmouseover=\"this.className='color_icon_over'\" onmouseout=\"this.className='color_icon'\" onclick=\"setColor('"+code+"')\" alt=\"color\" \/><\/div>";
}
