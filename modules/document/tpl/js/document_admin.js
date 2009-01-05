
/**
 * @brief 모든 생성된 썸네일 삭제하는 액션 호출
 **/
function doDeleteAllThumbnail() {
    exec_xml('document','procDocumentAdminDeleteAllThumbnail',new Array(), completeDeleteAllThumbnail);
}

function completeDeleteAllThumbnail(ret_obj) {
    alert(ret_obj['message']);
    location.reload();
}

/* 선택된 글의 삭제 또는 이동 */
function doManageDocument(type) {
    var fo_obj = jQuery("#fo_management").get(0);
    fo_obj.type.value = type;

    procFilter(fo_obj, manage_checked_document);
}

/* 선택된 글의 삭제 또는 이동 후 */
function completeManageDocument(ret_obj) {
    if(opener) opener.window.location.reload();
    alert(ret_obj['message']);
    window.close();
}

/* 선택된 모듈의 카테고리 목록을 가져오는 함수 */
function doGetCategoryFromModule(obj) {
    if(!obj) return;
    var module_srl = obj.options[obj.selectedIndex].value;

    var params = new Array();
    params['module_srl'] = module_srl;

    var response_tags = new Array('error','message','categories');

    exec_xml('document','getDocumentCategories',params, completeGetCategoryFromModules, response_tags);

}

function completeGetCategoryFromModules(ret_obj, response_tags) {
    var obj = jQuery('#target_category').get(0);
    var length = obj.options.length;
    for(var i=0;i<length;i++) obj.remove(0);

    var categories = ret_obj['categories'];
    if(!categories) return;

    var category_list = categories.split("\n");
    for(var i=0;i<category_list.length;i++) {
        var item = category_list[i];

        var pos = item.indexOf(',');
        var category_srl = item.substr(0,pos);

        var item = item.substr(pos+1, item.length);
        var pos = item.indexOf(',');
        var depth = item.substr(0,pos);

        var category_title = item.substr(pos+1,item.length);
        if(!category_srl || !category_title) continue;

        var opt = new Option(category_title, category_srl, false, false);
        if(depth>0) opt.style.paddingLeft = (depth*15)+'px';
        obj.options[obj.options.length] = opt;
    }
}

function doCancelDeclare() {
    var document_srl = new Array();
    jQuery('#fo_list input[name=cart]:checked').each(function() {
        document_srl[document_srl.length] = jQuery(this).val();
    });

    if(document_srl.length<1) return;

    var params = new Array();
    params['document_srl'] = document_srl.join(',');

    exec_xml('document','procDocumentAdminCancelDeclare', params, completeCancelDeclare);
}

function completeCancelDeclare(ret_obj) {
    location.reload();
}
