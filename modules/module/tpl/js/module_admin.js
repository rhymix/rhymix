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
    location.reload();
}

/* 모듈 업그레이드 */
function doUpdateModule(module) {
    var params = new Array();
    params['module_name'] = module;
    exec_xml('install','procInstallAdminUpdate',params, completeInstallModule);
}

/* 모듈 복사후 */
function completeCopyModule() {
    if(typeof(opener)!='undefined') opener.location.href = opener.location.href;
    window.close();
}

/* 모듈 선택기에서 선택된 모듈의 입력 */
function insertModule(id, module_srl, mid, browser_title, multi_select) {
    if(typeof(multi_select)=='undefined') multi_select = true;
    if(!window.opener) window.close();

    if(multi_select) {
        if(typeof(opener.insertSelectedModules)=='undefined') return;
        opener.insertSelectedModules(id, module_srl, mid, browser_title);
    } else {
        if(typeof(opener.insertSelectedModule)=='undefined') return;
        opener.insertSelectedModule(id, module_srl, mid, browser_title);
        window.close();
    }
}

/* 권한 선택용 */
function doShowGrantZone() {
    jQuery(".grant_default").each( function() {
        var id = "#zone_"+this.name.replace(/_default$/,'');
        if(!jQuery(this).val()) jQuery(id).css("display","block");
        else jQuery(id).css("display","none");
    } );
}

/* 권한 등록 후 알림 메세지 */
function completeInsertGrant(ret_obj) {
    alert(ret_obj['message']);
    location.reload();
}

/* 관리자 아이디 등록/ 제거 */
function doInsertAdmin() {
    var fo_obj = xGetElementById("fo_obj");
    var sel_obj = fo_obj._admin_member;
    var admin_id = fo_obj.admin_id.value;
    if(!admin_id) return;

    var opt = new Option(admin_id,admin_id,true,true);
    sel_obj.options[sel_obj.options.length] = opt;

    fo_obj.admin_id.value = '';
    sel_obj.size = sel_obj.options.length;
    sel_obj.selectedIndex = -1;

    var members = new Array();
    for(var i=0;i<sel_obj.options.length;i++) {
        members[members.length] = sel_obj.options[i].value;
        
    }
    fo_obj.admin_member.value = members.join(',');

    fo_obj.admin_id.focus();
}

function doDeleteAdmin() {
    var fo_obj = xGetElementById("fo_obj");
    var sel_obj = fo_obj._admin_member;
    sel_obj.remove(sel_obj.selectedIndex);

    sel_obj.size = sel_obj.options.length;
    sel_obj.selectedIndex = -1;

    var members = new Array();
    for(var i=0;i<sel_obj.options.length;i++) {
        members[members.length] = sel_obj.options[i].value;
        
    }
    fo_obj.admin_member.value = members.join(',');
}


function completeModuleSetup(ret_obj) {
    alert(ret_obj['message']);
    window.close();
}

/**
 * 언어 관련
 **/
function doInsertLangCode(name) {
    var fo_obj = xGetElementById("menu_fo");
    var target = fo_obj.target.value;
    if(window.opener && target) {
        var obj = window.opener.xGetElementById(target);
        if(obj) obj.value = '$user_lang->'+name;
    }
    window.close();
}

function completeInsertLang(ret_obj) {
    doInsertLangCode(ret_obj['name']);
}

function doDeleteLang(name) {
    var params = new Array();
    params['name'] = name;
    var response_args = new Array('error','message');
    exec_xml('module','procModuleAdminDeleteLang',params, completeDeleteLang);
}

function completeDeleteLang(ret_obj) {
    location.href = current_url.setQuery('name','');
}

function doFillLangName() {
    var fo_obj = xGetElementById("menu_fo");
    var target = fo_obj.target.value;
    if(window.opener && window.opener.xGetElementById(target)) {
        var value = window.opener.xGetElementById(target).value;
        if(/^\$user_lang->/.test(value)) {
            var param = new Array();
            param['name'] = value.replace(/^\$user_lang->/,'');
            var response_tags = new Array('error','message','name','langs');
            exec_xml('module','getModuleAdminLangCode',param,completeFillLangName, response_tags);
        }
    }
}

function completeFillLangName(ret_obj, response_tags) {
    var name = ret_obj['name'];
    var langs = ret_obj['langs'];
    if(typeof(langs)=='undefined') return;
    var fo_obj = xGetElementById("menu_fo");
    fo_obj.lang_code.value = name;
    for(var i in langs) {
        fo_obj[i].value = langs[i];
    }

}
