/**
 * @file   modules/page/js/page_admin.js
 * @author zero (zero@nzeo.com)
 * @brief  page모듈의 관리자용 javascript
 **/

/* 모듈 생성 후 */
function completeInsertPage(ret_obj) {
    var error = ret_obj['error'];
    var message = ret_obj['message'];

    var page = ret_obj['page'];
    var module_srl = ret_obj['module_srl'];

    alert(message);

    var url = '';
    if(location.href.getQuery('module')=='admin') {
        url = current_url.setQuery('module_srl',module_srl).setQuery('act','dispPageAdminInfo');
        if(page) url = url.setQuery('page',page);
    } else {
        url = current_url.setQuery('act','').setQuery('module_srl','');
    }

    location.href = url;
}

/* 내용 저장 후 */
function completeInsertPageContent(ret_obj) {
    var error = ret_obj['error'];
    var message = ret_obj['message'];

    var page = ret_obj['page'];
    var module_srl = ret_obj['module_srl'];
    var mid = ret_obj['mid'];

    location.href = current_url.setQuery('mid',mid).setQuery('act','');
}

/* 수정한 페이지 컨텐츠를 저장 */
function doSubmitPageContent(fo_obj) {
    var html = getWidgetContent();
    fo_obj.content.value = html;
    return procFilter(fo_obj, insert_page_content);
}

/* 모듈 삭제 후 */
function completeDeletePage(ret_obj) {
    var error = ret_obj['error'];
    var message = ret_obj['message'];
    var page = ret_obj['page'];
    alert(message);

    var url = current_url.setQuery('act','dispPageAdminContent');
    if(page) url = url.setQuery('page',page);

    location.href = url;
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

/* 위젯 재컴파일 */
function doRemoveWidgetCache(module_srl) {
    var params = new Array();
    params["module_srl"] = module_srl;
    exec_xml('page', 'procPageAdminRemoveWidgetCache', params, completeRemoveWidgetCache);
}

function completeRemoveWidgetCache(ret_obj) {
    var message = ret_obj['message'];
    location.reload(); 
}

/* 일괄 설정 */
function doCartSetup(url) {
    var module_srl = new Array();
    jQuery('#fo_list input[name=cart]:checked').each(function() {
        module_srl[module_srl.length] = jQuery(this).val();
    });

    if(module_srl.length<1) return;

    url += "&module_srls="+module_srl.join(',');
    popopen(url,'modulesSetup');
}
