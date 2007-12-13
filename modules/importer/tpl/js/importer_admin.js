/**
 * @file   modules/importer/js/importer_admin.js
 * @author zero (zero@nzeo.com)
 * @brief  importer에서 사용하는 javascript
 **/
/* 회원정보와 게시물의 싱크 */
function doSync(fo_obj) {
    exec_xml('importer','procImporterAdminSync', new Array(), completeSync);
    return false;
}

function completeSync(ret_obj) {
    alert(ret_obj['message']);
    location.href=location.href;
}


/* 회원정보 데이터 import */
function doImportMember(fo_obj) {
    var xml_file = fo_obj.xml_file.value;
    if(!xml_file) return false;

    var params = new Array();
    params['xml_file'] = xml_file;
    params['total_count'] = fo_obj.total_count.value;
    params['success_count'] = fo_obj.success_count.value;
    params['readed_line'] = fo_obj.readed_line.value;

    var response_tags = new Array("error","message", "total_count", "success_count", "readed_line", "is_finished");

    fo_obj.xml_file.disabled = true;
    xGetElementById("status").style.display = "block";
    xGetElementById("status_button_prev").style.display = "none";
    xGetElementById("status_button").style.display = "block";

    exec_xml('importer','procImporterAdminMemberImport', params, completeImportMember, response_tags);

    return false;
}

function completeImportMember(ret_obj) {
    var total_count = ret_obj['total_count'];
    var success_count = ret_obj['success_count'];
    var readed_line = ret_obj['readed_line'];
    var is_finished = ret_obj['is_finished'];

    if(is_finished == '1') {
        alert(ret_obj['message']);
        xInnerHtml("status", ret_obj['message']);
    } else {
        xInnerHtml("status", ret_obj['message']);

        var fo_obj = xGetElementById("fo_import");
        fo_obj.total_count.value = total_count;
        fo_obj.success_count.value = success_count;
        fo_obj.readed_line.value = readed_line;
        doImportMember(fo_obj);
    }
}

/* 모듈 데이터 import */
function doImportModule(fo_obj) {
    var target_module = fo_obj.target_module.options[fo_obj.target_module.selectedIndex].value;
    if(!target_module) return false;

    var xml_file = fo_obj.xml_file.value;
    if(!xml_file) return false;

    var params = new Array();
    params['xml_file'] = xml_file;
    params['target_module'] = target_module;
    params['total_count'] = fo_obj.total_count.value;
    params['success_count'] = fo_obj.success_count.value;
    params['readed_line'] = fo_obj.readed_line.value;

    var response_tags = new Array("error","message", "total_count", "success_count", "readed_line", "is_finished");

    fo_obj.target_module.disabled = true;
    fo_obj.xml_file.disabled = true;
    xGetElementById("status").style.display = "block";
    xGetElementById("status_button_prev").style.display = "none";
    xGetElementById("status_button").style.display = "block";

    exec_xml('importer','procImporterAdminModuleImport', params, completeImportModule, response_tags);

    return false;
}

function completeImportModule(ret_obj, response_tags) {
    var total_count = ret_obj['total_count'];
    var success_count = ret_obj['success_count'];
    var readed_line = ret_obj['readed_line'];
    var is_finished = ret_obj['is_finished'];

    if(is_finished == '1') {
        alert(ret_obj['message']);
        xInnerHtml("status", ret_obj['message']);
    } else {
        xInnerHtml("status", ret_obj['message']);

        var fo_obj = xGetElementById("fo_import");
        fo_obj.total_count.value = total_count;
        fo_obj.success_count.value = success_count;
        fo_obj.readed_line.value = readed_line;
        doImportModule(fo_obj);
    }
}

