/**
 * @file   modules/blog/js/blog_admin.js
 * @author zero (zero@nzeo.com)
 * @brief  blog 모듈의 관리자용 javascript
 **/

/* 모듈 생성 후 */
function completeInsertBlog(ret_obj) {
    var error = ret_obj['error'];
    var message = ret_obj['message'];

    var page = ret_obj['page'];
    var module_srl = ret_obj['module_srl'];

    alert(message);

    var url = location.href.setQuery('module_srl',module_srl).setQuery('act','dispBlogAdminBlogInfo');
    if(page) url.setQuery('page',page);
    location.href = url;
}

/* 모듈 삭제 후 */
function completeDeleteBlog(ret_obj) {
    var error = ret_obj['error'];
    var message = ret_obj['message'];
    var page = ret_obj['page'];
    alert(message);

    var url = location.href.setQuery('act','dispBlogAdminContent').setQuery('module_srl','');
    if(page) url = url.setQuery('page',page);
    location.href = url;
}

/* 카테고리 관련 작업들 */
function doUpdateCategory(category_srl, mode, message) {
    if(typeof(message)!='undefined'&&!confirm(message)) return;

    var fo_obj = xGetElementById('fo_category_info');
    fo_obj.category_srl.value = category_srl;
    fo_obj.mode.value = mode;

    procFilter(fo_obj, update_category);
}

/* 카테고리 정보 수정 후 */
function completeUpdateCategory(ret_obj) {
    var error = ret_obj['error'];
    var message = ret_obj['message'];
    var module_srl = ret_obj['module_srl'];
    var page = ret_obj['page'];
    alert(message);

    var url = location.href.setQuery('module_srl',module_srl).setQuery('act','dispBlogAdminCategoryInfo');
    if(page) url.setQuery('page',page);
    location.href = url;
}

/* 권한 관련 */
function doSelectAll(obj, key) {
    var fo_obj = obj.parentNode;
    while(fo_obj.nodeName != 'FORM') { 
        fo_obj = fo_obj.parentNode; 
    }

    for(var i=0;i<fo_obj.length;i++) {
        var tobj = fo_obj[i];
        if(tobj.name == key) tobj.checked=true;
    }
}

function doUnSelectAll(obj, key) {
    var fo_obj = obj.parentNode;
    while(fo_obj.nodeName != 'FORM') { 
        fo_obj = fo_obj.parentNode; 
    }

    for(var i=0;i<fo_obj.length;i++) {
        var tobj = fo_obj[i];
        if(tobj.name == key) tobj.checked = false;
    }
}

function completeInsertGrant(ret_obj) {
    var error = ret_obj['error'];
    var message = ret_obj['message'];
    var page = ret_obj['page'];
    var module_srl = ret_obj['module_srl'];

    alert(message);

    var url = location.href.setQuery('module_srl',module_srl).setQuery('act','dispBlogAdminGrantInfo');
    if(page) url.setQuery('page',page);
    location.href = url;
}

/* 카테고리 이동 */
function doChangeCategory(sel_obj, url) {
    var module_category_srl = sel_obj.options[sel_obj.selectedIndex].value;
    if(!module_category_srl) location.href=url;
    else location.href=url+'&module_category_srl='+module_category_srl;
}

/* 선택된 글의 삭제 또는 이동 */
function doManageDocument(type, mid) {
    var fo_obj = xGetElementById("fo_management");
    fo_obj.type.value = type;

    procFilter(fo_obj, manage_checked_document);
}

/* 선택된 글의 삭제 또는 이동 후 */
function completeManageDocument(ret_obj) {
    if(opener) opener.location.href = opener.location.href;
    alert(ret_obj['message']);
    window.close();
}

/**
 * 카테고리 관리
 **/ 

/* 빈 카테고리 아이템 추가 */
function doInsertCategory(parent_srl) {
    if(typeof(parent_srl)=='undefined') parent_srl = 0;
    var params = {node_srl:0, parent_srl:parent_srl}
    doGetCategoryInfo('category', params);
    deSelectNode();
}

/* 카테고리 클릭시 적용할 함수 */
function doGetCategoryInfo(category_id, obj) {
    // category, category_id, node_srl을 추출
    var fo_obj = xGetElementById("fo_category");
    var node_srl = 0;
    var parent_srl = 0;

    if(typeof(obj)!="undefined") {
        if(typeof(obj.getAttribute)!="undefined") { 
          node_srl = obj.getAttribute("node_srl");
        } else {
            node_srl = obj.node_srl; 
            parent_srl = obj.parent_srl; 
        }
    }

    var params = new Array();
    params["category_srl"] = node_srl;
    params["parent_srl"] = parent_srl;

    // 서버에 요청하여 해당 노드의 정보를 수정할 수 있도록 한다. 
    var response_tags = new Array('error','message','tpl');
    exec_xml('blog', 'getBlogAdminCategoryTplInfo', params, completeGetCategoryTplInfo, response_tags, params);
}

/* 서버로부터 받아온 카테고리 정보를 출력 */
function completeGetCategoryTplInfo(ret_obj, response_tags) {
    var tpl = ret_obj['tpl'];
    xInnerHtml("category_zone_info", tpl);
    var fo_obj = xGetElementById("fo_category");
    fo_obj.name.focus();
}

/* 카테고리 아이템 입력후 */ 
function completeInsertCategory(ret_obj) {
    var category_id = ret_obj['category_id'];
    var xml_file = ret_obj['xml_file'];
    var category_title = ret_obj['category_title'];
    var category_srl = ret_obj['category_srl'];
    var category_category_srl = ret_obj['category_category_srl'];
    var parent_srl = ret_obj['parent_srl'];

    if(!xml_file) return;

    loadTreeMenu(xml_file, 'category', 'category_zone_category', category_title, doGetCategoryInfo, category_category_srl, doMoveTree);

    if(!category_srl) xInnerHtml("category_zone_info", "");
    else {
        var params = {node_srl:category_zone_info, parent_srl:parent_srl}
        doGetCategoryInfo('category', params)
    }
} 


/* 카테고리를 드래그하여 이동한 후 실행할 함수 , 이동하는 category_srl과 대상 category_srl을 받음 */
function doMoveTree(category_id, source_category_srl, target_category_srl) {
    var fo_obj = xGetElementById("fo_move_category");
    fo_obj.category_id.value = category_id;
    fo_obj.source_category_srl.value = source_category_srl;
    fo_obj.target_category_srl.value = target_category_srl;

    // 이동 취소를 선택하였을 경우 다시 그림;;
    if(!procFilter(fo_obj, move_category)) {
        var params = new Array();
        params["xml_file"] = xGetElementById('fo_category').xml_file.value;
        params["source_category_srl"] = source_category_srl;
        completeMoveCategory(params);
    }
}

function completeMoveCategory(ret_obj) {
    var source_category_srl = ret_obj['source_category_srl'];
    var xml_file = ret_obj['xml_file'];

    var fo_category = xGetElementById("fo_category");
    if(!fo_category) return;

    var title = fo_category.title.value;
    loadTreeMenu(xml_file, 'category', "category_zone_category", title, doGetCategoryInfo, source_category_srl, doMoveTree);
}

/* 카테고리 목록 갱신 */
function doReloadTreeMenu(module_srl) {
    var params = new Array();
    params["module_srl"] = module_srl;

    // 서버에 요청하여 해당 노드의 정보를 수정할 수 있도록 한다. 
    var response_tags = new Array('error','message', 'xml_file', 'category_title');
    exec_xml('blog', 'procBlogAdminMakeXmlFile', params, completeInsertCategory, response_tags, params);
}

/* 카테고리 삭제 */
function doDeleteCategoryItem(category_srl) {
      var fo_obj = xGetElementById("fo_category");
      if(!fo_obj) return;

      procFilter(fo_obj, delete_category);
}

/* 카테고리 아이템 삭제 후 */ 
function completeDeleteCategory(ret_obj) {
    var category_title = ret_obj['category_title'];
    var module_srl = ret_obj['module_srl'];
    var category_srl = ret_obj['category_srl'];
    var xml_file = ret_obj['xml_file'];
    alert(ret_obj['message']);

    loadTreeMenu(xml_file, 'category', 'category_zone_category', category_title, doGetCategoryInfo, category_srl, doMoveTree);
    xInnerHtml("category_zone_info", "");
} 

