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

    alert(message);

    var url = current_url.setQuery('act','dispBlogAdminBlogInfo');
    location.href = url;
}

/* 모듈 삭제 후 */
function completeDeleteBlog(ret_obj) {
    var error = ret_obj['error'];
    var message = ret_obj['message'];
    var page = ret_obj['page'];
    alert(message);

    var url = current_url.setQuery('act','dispBlogAdminContent').setQuery('module_srl','');
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

    var url = current_url.setQuery('module_srl',module_srl).setQuery('act','dispBlogAdminCategoryInfo');
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

    var url = current_url.setQuery('module_srl',module_srl).setQuery('act','dispBlogAdminGrantInfo');
    if(page) url.setQuery('page',page);
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
    doGetCategoryInfo(null, params);
    deSelectNode();
}

/* 카테고리 클릭시 적용할 함수 */
function doGetCategoryInfo(category_id, obj) {
    // category, category_id, node_srl을 추출
    var fo_obj = xGetElementById("fo_category");
    var module_srl = fo_obj.module_srl.value;
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
    params["module_srl"] = module_srl;

    // 서버에 요청하여 해당 노드의 정보를 수정할 수 있도록 한다. 
    var response_tags = new Array('error','message','tpl');
    exec_xml('blog', 'getBlogAdminCategoryTplInfo', params, completeGetCategoryTplInfo, response_tags, params);
}

/* 서버로부터 받아온 카테고리 정보를 출력 */
xAddEventListener(document,'mousedown',checkMousePosition);
var _xPos = 0;
var _yPos = 0;
function checkMousePosition(e) {
    var evt = new xEvent(e);
    _xPos = evt.pageX;
    _yPos = evt.pageY;
}

function hideCategoryInfo() {
    var obj = xGetElementById('category_info');
    obj.style.display = "none";
}

function completeGetCategoryTplInfo(ret_obj, response_tags) {
    var obj = xGetElementById('category_info');
    obj.style.marginTop = xScrollTop()+'px';
    var tpl = ret_obj['tpl'];
    xInnerHtml(obj, tpl);
    obj.style.display = 'block';

    var fo_obj = xGetElementById("fo_category");
    fo_obj.category_title.focus();

    var x = _xPos + 50;
    var y = _yPos - xHeight(obj)/2+ xScrollTop();
    xLeft(obj, x);
    xTop(obj, y);
    xRemoveEventListener(document,'mousedown',checkMousePosition);

    if(xGetElementById('cBody') && xHeight('cBody')< y+xHeight(obj)+50) {
        xHeight('cBody', y + xHeight(obj) + 50);
    }

    if(typeof('fixAdminLayoutFooter')=="function") fixAdminLayoutFooter();
}

/* 카테고리 아이템 입력후 */ 
function completeInsertCategory(ret_obj) {
    var xml_file = ret_obj['xml_file'];
    var category_srl = ret_obj['category_srl'];
    var module_srl = ret_obj['module_srl'];
    var parent_srl = ret_obj['parent_srl'];

    if(!xml_file) return;

    loadTreeMenu(xml_file, 'category', 'zone_category', category_title, '',doGetCategoryInfo, category_srl, doMoveTree);

    if(!category_srl) {
        xInnerHtml("category_info", "");
    } else {
        var params = {node_srl:category_srl, parent_srl:parent_srl}
        doGetCategoryInfo(null, params)
    }

    if(typeof('fixAdminLayoutFooter')=="function") fixAdminLayoutFooter();
} 


/* 카테고리를 드래그하여 이동한 후 실행할 함수 , 이동하는 category_srl과 대상 category_srl을 받음 */
function doMoveTree(category_id, source_category_srl, target_category_srl) {
    source_category_srl = source_category_srl.replace(/menu_category_/,'');
    target_category_srl = target_category_srl.replace(/menu_category_/,'');
    var p_fo_obj = xGetElementById("fo_category");

    var fo_obj = xGetElementById("fo_move_category");
    fo_obj.source_category_srl.value = source_category_srl;
    fo_obj.target_category_srl.value = target_category_srl;
    fo_obj.module_srl.value = p_fo_obj.module_srl.value;

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

    loadTreeMenu(xml_file, 'category', "zone_category", category_title, '', doGetCategoryInfo, source_category_srl, doMoveTree);
}

/* 카테고리 목록 갱신 */
function doReloadTreeCategory(module_srl) {
    var params = new Array();
    params["module_srl"] = module_srl;

    // 서버에 요청하여 해당 노드의 정보를 수정할 수 있도록 한다. 
    var response_tags = new Array('error','message', 'xml_file');
    exec_xml('blog', 'procBlogAdminMakeXmlFile', params, completeInsertCategory, response_tags, params);
}

/* 카테고리 삭제 */
function doDeleteCategory(category_srl) {
      var fo_obj = xGetElementById("fo_category");
      if(!fo_obj) return;

      procFilter(fo_obj, delete_category);
}

/* 카테고리 아이템 삭제 후 */ 
function completeDeleteCategory(ret_obj) {
    var module_srl = ret_obj['module_srl'];
    var category_srl = ret_obj['category_srl'];
    var xml_file = ret_obj['xml_file'];
    alert(ret_obj['message']);

    loadTreeMenu(xml_file, 'category', 'zone_category', category_title, '', doGetCategoryInfo, category_srl, doMoveTree);

    var obj = xGetElementById('category_info');
    xInnerHtml(obj, "");
    obj.style.display = 'none';
} 

