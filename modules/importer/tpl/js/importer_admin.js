/**
 * @file   modules/importer/js/importer_admin.js
 * @author zero (zero@nzeo.com)
 * @brief  importer에서 사용하는 javascript
 **/

/**
 * 회원정보와 게시글/댓글등의 동기화 요청 및 결과 처리 함수
 **/
function doSync(fo_obj) {
    exec_xml('importer','procImporterAdminSync', new Array(), completeSync);
    return false;
}

function completeSync(ret_obj) {
    alert(ret_obj['message']);
    location.href=location.href;
}


/**
 * xml파일을 DB입력전에 extract를 통해 분할 캐싱을 요청하는 함수
 **/
var prepared = false;
function doPreProcessing(fo_obj) {
    var xml_file = fo_obj.xml_file.value;
    if(!xml_file) return false;

    var type = fo_obj.type.value;

    xDisplay('importForm','none');
    xDisplay('process','block');
    xInnerHtml('status','');
    setTimeout(doPrepareDot, 50);

    var params = new Array();
    params['xml_file'] = xml_file;
    params['type'] = type;

    var response_tags = new Array('error','message','type','total','cur','key','status');
    exec_xml('importer','procImporterAdminPreProcessing', params, completePreProcessing, response_tags);

    return false;
}

/* 준비중일때 .(dot) 찍어주는.. */
function doPrepareDot() {
    if(prepared) return;

    var str = xInnerHtml('status');
    if(str.length<1 || str.length - preProcessingMsg.length > 50) str = preProcessingMsg;
    else str += ".";
    xInnerHtml('status', str);
    setTimeout(doPrepareDot, 50);
}

/* 준비가 끝났을때 호출되는 함수 */
function completePreProcessing(ret_obj, response_tags) {
    prepared = true;
    xInnerHtml('status','');

    var status = ret_obj['status'];
    var message = ret_obj['message'];
    var type = ret_obj['type'];
    var total = parseInt(ret_obj['total'],10);
    var cur = parseInt(ret_obj['cur'],10);
    var key = ret_obj['key'];

    if(status == -1) {
        xDisplay('importForm','block');
        xDisplay('process','none');
        xDisplay('btn_reload','block');
        xDisplay('btn_continue','none');
        alert(message);
        return;
    }

    xDisplay('btn_reload','none');
    xDisplay('btn_continue','block');

    var fo_obj = xGetElementById('fo_process');
    fo_obj.type.value = type;
    fo_obj.total.value = total;
    fo_obj.cur.value = cur;
    fo_obj.key.value = key;

    var fo_import = xGetElementById('fo_import');
    if(fo_import && fo_import.target_module) fo_obj.target_module.value = fo_import.target_module.options[fo_import.target_module.selectedIndex].value;
    if(fo_import && fo_import.user_id) fo_obj.user_id.value = fo_import.user_id.value;

    fo_obj.unit_count.value = fo_import.unit_count.options[fo_import.unit_count.selectedIndex].value;
    
    // extract된 파일을 이용해서 import
    doImport();
}

/* @brief 임포트 시작 */
function doImport() {
    var fo_obj = xGetElementById('fo_process');

    var params = new Array();
    params['type'] = fo_obj.type.value;
    params['total'] = fo_obj.total.value;
    params['cur'] = fo_obj.cur.value;
    params['key'] = fo_obj.key.value;
    params['target_module'] = fo_obj.target_module.value;
    params['unit_count'] = fo_obj.unit_count.value;
    params['user_id'] = fo_obj.user_id.value;

    displayProgress(params['total'], params['cur']);

    var response_tags = new Array('error','message','type','total','cur','key');

    show_waiting_message = false;
    exec_xml('importer','procImporterAdminImport', params, completeImport, response_tags);
    show_waiting_message = true;

    return false;
}


/* import중 표시 */
function completeImport(ret_obj, response_tags) {
    var message = ret_obj['message'];
    var type = ret_obj['type'];
    var total = parseInt(ret_obj['total'],10);
    var cur = parseInt(ret_obj['cur'],10);
    var key = ret_obj['key'];

    displayProgress(total, cur);

    var fo_obj = xGetElementById('fo_process');
    fo_obj.type.value = type;
    fo_obj.total.value = total;
    fo_obj.cur.value = cur;
    fo_obj.key.value = key;
    
    // extract된 파일을 이용해서 import
    if(total>cur) doImport();
    else {
        alert(message);
        fo_obj.reset();
        xDisplay('process','none');
        xDisplay('importForm','block');
        xGetElementById('fo_import').reset();
    }
}

/* 상태 표시 함수 */
function displayProgress(total, cur) {
    // 진행률 구함
    var per = 0;
    if(total > 0) per = Math.round(cur/total*100);
    else per = 100;
    if(!per) per = 1;

    var status = '<div class="progressBox"><div class="progress1" style="width:'+per+'%;">'+per+'%&nbsp;</div>';
    status += '<div class="progress2">'+cur+'/'+total+'</div>';
    status += '<div class="clear"></div></div>';
    xInnerHtml('status', status);
}
