/**
 * @file     modules/module/js/module_admin.js
 * @author zero (zero@nzeo.com)
 * @brief    module 모듈의 관리자용 javascript
 **/

/* 카테고리 관련 작업들 */
function doUpdateCategory(module_category_srl, mode, message) {
    if(typeof(message)!='undefined'&&!confirm(message)) return;

    var fo_obj = xGetElementById('fo_category_info');
    fo_obj.module_category_srl.value = module_category_srl;
    fo_obj.mode.value = mode;

    procFilter(fo_obj, update_category);
}

/* 카테고리 정보 수정 후 */
function completeUpdateCategory(ret_obj) {
    var error = ret_obj['error'];
    var message = ret_obj['message'];

    alert(message);

    location.href =  current_url.setQuery('module_category_srl','');
}

/* 선택된 모듈을 관리자 메뉴의 바로가기에 등록 */
function doAddShortCut(module) {
    var fo_obj = xGetElementById("fo_shortcut");
    fo_obj.selected_module.value = module;
    procFilter(fo_obj, insert_shortcut);
}

/* 모듈 설치 */
function doInstallModule(module) {
    var params = new Array();
    params['module_name'] = module;
    exec_xml('install','procInstallAdminInstall',params, completeInstallModule);
}

function completeInstallModule(ret_obj) {
    alert(ret_obj['message']);
    location.href = location.href;
}

/* 모듈 업그레이드 */
function doUpdateModule(module) {
    var params = new Array();
    params['module_name'] = module;
    exec_xml('install','procInstallAdminUpdate',params, completeInstallModule);
}
