/**
 * @author zero (zero@nzeo.com)
 * @version 0.1
 * @brief 에디터 관리자 페이지용 스크립트 
 **/

function doEnableComponent(component_name) {
    var params = new Array();
    params['component_name'] = component_name;

    exec_xml('editor', 'procEditorAdminEnableComponent', params, completeUpdate);
}

function doDisableComponent(component_name) {
    var params = new Array();
    params['component_name'] = component_name;

    exec_xml('editor', 'procEditorAdminDisableComponent', params, completeUpdate);
}

function doMoveListOrder(component_name, mode) {
    var params = new Array();
    params['component_name'] = component_name;
    params['mode'] = mode;

    exec_xml('editor', 'procEditorAdminMoveListOrder', params, completeUpdate);
}

function completeUpdate(ret_obj) {
    alert(ret_obj['message']);
    location.href = location.href;
}

function doSetupComponent(component_name) {
    winopen("./?module=editor&act=dispEditorAdminSetupComponent&component_name="+component_name, "SetupComponent","width=10,height=10,scrollbars=no,resizable=no,toolbars=no");
}
