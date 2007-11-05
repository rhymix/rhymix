/**
 * @file   admin.js
 * @author zero (zero@nzeo.com)
 * @brief  admin 모듈의 javascript
 **/

// 숏컷 삭제
function doDeleteShortCut(selected_module) {
    var fo_obj = xGetElementById('fo_shortcut_info');
    fo_obj.selected_module.value = selected_module;
    procFilter(fo_obj, delete_shortcut);
}

// 캐시파일 모두 재 생성
function doRecompileCacheFile() {
    exec_xml("admin","procAdminRecompileCacheFile");
}

// footer를 화면 크기에 맞춰 설정 (폐기)
//xAddEventListener(window, 'load', fixAdminLayoutFooter);
//xAddEventListener(window, 'resize', fixAdminLayoutFooter);
function fixAdminLayoutFooter(height) {
    return;
}

function setMenuContentScale() {
    var menuHeight = xHeight("adminMenuContent")+70;
    var bodyHeight = xHeight("adminContentBody");
    if(bodyHeight>menuHeight) xHeight("adminMenuContent", bodyHeight-70);
    else if(bodyHeight<menuHeight) xHeight("adminContentBody", menuHeight);
}

// 메뉴 여닫기
function toggleAdminMenu(id) {
    var obj = xGetElementById(id);
    var rh = 0;
    if(obj.style.display == 'none') {
        obj.style.display = 'block';
        rh = xHeight(obj);
        xHeight("adminMenuContent", xHeight('adminMenuContent')+rh);
    } else {
        rh = xHeight(obj);
        obj.style.display = 'none';
        xHeight("adminMenuContent", xHeight('adminMenuContent')-rh);
    }

    var expire = new Date();
    expire.setTime(expire.getTime()+ (7000 * 24 * 3600000));
    xSetCookie(id, obj.style.display, expire);

    setMenuContentScale();
}
