/**
 * @file   modules/springnote/js/springnote_admin.js
 * @author zero (zero@nzeo.com)
 * @brief  springnote 모듈의 관리자용 javascript
 **/

/* 모듈 생성 후 */
function completeInsertSpringnote(ret_obj) {
    var error = ret_obj['error'];
    var message = ret_obj['message'];

    var page = ret_obj['page'];
    var module_srl = ret_obj['module_srl'];

    alert(message);

    var url = current_url.setQuery('act','dispSpringnoteAdminSpringnoteInfo');
    if(module_srl) url = url.setQuery('module_srl',module_srl);
    if(page) url.setQuery('page',page);
    location.href = url;
}

/* 모듈 삭제 후 */
function completeDeleteSpringnote(ret_obj) {
    var error = ret_obj['error'];
    var message = ret_obj['message'];
    var page = ret_obj['page'];
    alert(message);

    var url = current_url.setQuery('act','dispSpringnoteAdminContent').setQuery('module_srl','');
    if(page) url = url.setQuery('page',page);
    location.href = url;
}

/* 권한 관련 */
function completeInsertGrant(ret_obj) {
    var error = ret_obj['error'];
    var message = ret_obj['message'];
    var page = ret_obj['page'];
    var module_srl = ret_obj['module_srl'];

    alert(message);

    location.href = location.href;
}

/* 카테고리 이동 */
function doChangeCategory(fo_obj) {
    var module_category_srl = fo_obj.module_category_srl.options[fo_obj.module_category_srl.selectedIndex].value;
    if(module_category_srl==-1) {
        location.href = current_url.setQuery('act','dispModuleAdminCategory');
        return false;
    }
    return true;
}
