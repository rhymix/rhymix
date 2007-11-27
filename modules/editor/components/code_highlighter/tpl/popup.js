/**
 * popup으로 열렸을 경우 부모창의 위지윅에디터에 select된 멀티미디어 컴포넌트 코드를 체크하여
 * 있으면 가져와서 원하는 곳에 삽입
 **/
var selected_node = null;
function getCode() {
    // 부모 위지윅 에디터에서 선택된 영역이 있는지 확인
    if(typeof(opener)=="undefined") return;

    var node = opener.editorPrevNode;
    if(!node || node.nodeName != "DIV") return;

    selected_node = node;

    var code_type = node.getAttribute("code_type");

    xGetElementById("code_type").value = code_type;
}

/* 추가 버튼 클릭시 부모창의 위지윅 에디터에 인용구 추가 */
function insertCode() {
    if(typeof(opener)=="undefined") return;

    var code_type = xGetElementById("code_type").value;

    var content = "";
    if(selected_node) content = xInnerHtml(selected_node);
    else content = opener.editorGetSelectedHtml(opener.editorPrevSrl);

    var style = "border: #666666 1px dotted; border-left: #22aaee 5px solid; padding: 5px; background: #FAFAFA url('./modules/editor/components/code_highlighter/code.png') no-repeat top right;";

    if(!content) content = "&nbsp;";

    var text = "\n<div editor_component=\"code_highlighter\" code_type="+code_type+" style=\""+style+"\">"+content+"</div>\n<br />";

    if(selected_node) {
        selected_node.setAttribute("code_type", code_type);
        selected_node.setAttribute("style", style);
        opener.editorFocus(opener.editorPrevSrl);

    } else {

        opener.editorFocus(opener.editorPrevSrl);
        var iframe_obj = opener.editorGetIFrame(opener.editorPrevSrl)
        opener.editorReplaceHTML(iframe_obj, text);
        opener.editorFocus(opener.editorPrevSrl);
    }

    window.close();
}

xAddEventListener(window, "load", getCode);
