/**
 * popup으로 열렸을 경우 부모창의 위지윅에디터에 select된 멀티미디어 컴포넌트 코드를 체크하여
 * 있으면 가져와서 원하는 곳에 삽입
 **/
var selected_node = null;
function getCode() {
    // 부모 위지윅 에디터에서 선택된 영역이 있는지 확인
    if(typeof(opener)=='undefined') return;

    var node = opener.editorPrevNode;
    if(!node || node.nodeName != 'DIV') return;

    selected_node = node;

    var code_type = node.getAttribute('code_type');
    var file_path = node.getAttribute('file_path');
    var description = node.getAttribute('description');
    var first_line = node.getAttribute('first_line');
    var collapse = node.getAttribute('collapse');
    var nogutter = node.getAttribute('nogutter');
    var nocontrols = node.getAttribute('nocontrols');

    xGetElementById('code_type').value = code_type;
    xGetElementById('file_path').value = file_path;
    xGetElementById('description').value = description;
    if(!first_line) xGetElementById('first_line').value = '1';
    else xGetElementById('first_line').value = first_line;
    if(collapse == 'Y') xGetElementById('collapse').checked = true;
    if(nogutter == 'Y') xGetElementById('nogutter').checked = true;
    if(nocontrols == 'Y') xGetElementById('nocontrols').checked = true;
}

/* 추가 버튼 클릭시 부모창의 위지윅 에디터에 인용구 추가 */
function insertCode() {
    if(typeof(opener)=='undefined') return;

    var code_type = xGetElementById('code_type').value;
    var file_path = xGetElementById('file_path').value;
    var description = xGetElementById('description').value;
    var first_line = xGetElementById('first_line').value;
    var collapse = xGetElementById('collapse').checked;
    var nogutter = xGetElementById("nogutter").checked;
    var nocontrols = xGetElementById("nocontrols").checked;

    var content = '';
    if(selected_node) content = xInnerHtml(selected_node);
    else content = opener.editorGetSelectedHtml(opener.editorPrevSrl);

    var style = "border: #666666 1px dotted; border-left: #22aaee 5px solid; padding: 5px; background: #FAFAFA url('./modules/editor/components/code_highlighter/code.png') no-repeat top right;";

    if(!content) content = "&nbsp;";

    var text = '<div editor_component="code_highlighter" code_type="'+code_type+'" file_path="'+file_path+'" description="'+description+'" first_line="'+first_line+'" collapse="'+collapse+'" nogutter="'+nogutter+'" nocontrols="'+nocontrols+'" style="'+style+'">'+content+'</div>'+"<br />";

    if(selected_node) {
        selected_node.setAttribute('code_type', code_type);
        selected_node.setAttribute('file_path', file_path);
        selected_node.setAttribute('description', description);
        selected_node.setAttribute('first_line', first_line);
        selected_node.setAttribute("collapse", collapse);
        selected_node.setAttribute('nogutter', nogutter);
        selected_node.setAttribute('nocontrols', nocontrols);
        selected_node.setAttribute('style', style);
        opener.editorFocus(opener.editorPrevSrl);

    } else {

        opener.editorFocus(opener.editorPrevSrl);
        var iframe_obj = opener.editorGetIFrame(opener.editorPrevSrl)
        opener.editorReplaceHTML(iframe_obj, text);
        opener.editorFocus(opener.editorPrevSrl);
    }

    window.close();
}

xAddEventListener(window, 'load', getCode);
