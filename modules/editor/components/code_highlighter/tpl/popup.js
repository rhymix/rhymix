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

    jQuery('#code_type').val(code_type);
    jQuery('#file_path').val(file_path);
    jQuery('#description').val(description);
    if(!first_line) jQuery('#first_line').val('1');
    else jQuery('#first_line').val(first_line);
    if(collapse == 'Y' || collapse == 'true') jQuery('#collapse').attr('checked', true);
    if(nogutter == 'Y' || nogutter == 'true') jQuery('#nogutter').attr('checked', true);
    if(nocontrols == 'Y' || nocontrols == 'true') jQuery('#nocontrols').attr('checked', true);
}

/* 추가 버튼 클릭시 부모창의 위지윅 에디터에 인용구 추가 */
function insertCode() {
    if(typeof(opener)=='undefined') return;

    var code_type = jQuery('#code_type').val();
    var file_path = jQuery('#file_path').val();
    var description = jQuery('#description').val();
    var first_line = jQuery('#first_line').val();
    var collapse = jQuery('#collapse').attr('checked');
    var nogutter = jQuery('#nogutter').attr('checked');
    var nocontrols = jQuery('#nocontrols').attr('checked');

    var content = '';
    if(selected_node) content = jQuery(selected_node).html();
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

jQuery(getCode);

