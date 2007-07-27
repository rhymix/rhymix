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

// footer를 화면 크기에 맞춰 설정 (폐기)
//xAddEventListener(window, 'load', fixAdminLayoutFooter);
//xAddEventListener(window, 'resize', fixAdminLayoutFooter);
function fixAdminLayoutFooter(height) {
    return;
}

if(xIE6) {
    xAddEventListener(window,'load',fixAdminNaviHeight);
}

function fixAdminNaviHeight() {
    var naviHeight = xHeight('gNavigation');
    var bodyHeight = xHeight('content');
    if(naviHeight<bodyHeight) xHeight('gNavigation',bodyHeight);
    else xHeight('content',naviHeight);
    setTimeout(fixAdminNaviHeight, 500);
}
