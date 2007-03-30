/* 부모창의 위지윅 에디터의 선택된 영역의 글자색을 변경 */
function applyColor() {
    var code = xGetElementById("color_input").value;

    opener.editorFocus(opener.editorPrevSrl);

    opener.editorDo("ForeColor", "#"+code, opener.editorPrevSrl);

    opener.editorFocus(opener.editorPrevSrl);

    self.close();
}

/* 색상 클릭시 */
function select_color(code) {
    xGetElementById("color_input").value = code;
    xGetElementById("preview_color").style.backgroundColor = "#"+code;
}

/* 색상표를 출력 */
function printColor(blank_img_src) {
    var colorTable = new Array('22','44','66','88','AA','CC','EE');
    var html = "";

    for(var i=0;i<8;i+=1) html += printColorBlock(i.toString(16)+i.toString(16)+i.toString(16)+i.toString(16)+i.toString(16)+i.toString(16), blank_img_src);

    for(var i=0; i<colorTable.length; i+=3) {
        for(var j=0; j<colorTable.length; j+=2) {
            for(var k=0; k<colorTable.length; k++) {
                var code = colorTable[i] + colorTable[j] + colorTable[k];
                html += printColorBlock(code, blank_img_src);
            }
        }
    }

    for(var i=8;i<16;i+=1) html += printColorBlock(i.toString(16)+i.toString(16)+i.toString(16)+i.toString(16)+i.toString(16)+i.toString(16), blank_img_src);

    document.write(html);
}

/* 개별 색상 block 출력 함수 */
function printColorBlock(code, blank_img_src) {
    return "<div style=\"float:left;background-color:#"+code+"\"><img src=\""+blank_img_src+"\" class=\"color_icon\" onmouseover=\"this.className='color_icon_over'\" onmouseout=\"this.className='color_icon'\" onclick=\"select_color('"+code+"')\" alt=\"color\" \/><\/div>";
}

/* 수동 색상 변경시 */
function manual_select_color(obj) {
    if(obj.value.length!=6) return;
    xGetElementById("preview_color").style.backgroundColor = "#"+obj.value;
}
