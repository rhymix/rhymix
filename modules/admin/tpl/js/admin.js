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

// footer를 화면 크기에 맞춰 설정
xAddEventListener(window, 'load', fixAdminLayoutFooter);
xAddEventListener(window, 'resize', fixAdminLayoutFooter);
function fixAdminLayoutFooter(height) {
    var headerHeight = xHeight('header');
    var bodyHeight = xHeight('cBody');
    var footerHeight = xHeight('footer');
    var clientHeight = xClientHeight();
    var newHeight = clientHeight - footerHeight - headerHeight + 71 + 38;

    if(newHeight<bodyHeight) newHeight = bodyHeight;
    if(typeof(height)=='number') {
        newHeight += height;
    }
    xHeight('cBody', newHeight);
}
