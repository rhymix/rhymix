/**
 * @file   admin.js
 * @author zero (zero@nzeo.com)
 * @brief  admin 모듈의 javascript
 **/

xAddEventListener(document,'mouseup',showXESubMenu);
var openedSubMenus = null;
function showXESubMenu(evt) {
    var e = new xEvent(evt);
    if(e.target && /^adminMainMenu/.test(e.target.id)) {
        var key = e.target.id.split('_')[1];
        var obj = xGetElementById('adminSubMenu'+key);
        if(!obj) return;
        if(openedSubMenus) openedSubMenus.style.visibility = 'hidden';
        if(openedSubMenus == obj) {
            openedSubMenus = null;
            return;
        }
        openedSubMenus = obj;
        //xLeft(obj, xPageX(e.target) + (xWidth(e.target)-xWidth(obj))/2);
        //if(xLeft(obj) + xWidth(obj) + 10 > xClientWidth()) xLeft(obj, xClientWidth() - xWidth(obj) - 10);
        //xTop(obj, xPageY(e.target)+28);
        obj.style.visibility = 'visible';
        return;
    } else if(openedSubMenus) {
        openedSubMenus.style.visibility = 'hidden';
        openedSubMenus = null;
    }
}

// open/close Main Navigator
function toggleXEMainNavigator() {
    var obj = xGetElementsByClassName('xeAdmin')[0];
    var btnObj = xGetElementById('btnFolder');
    if(!obj) return;
    if(obj.style.display == 'none') {
        obj.style.display = 'block';
        btnObj.src = btnObj.src.replace(/btn_off.png/,'btn.png');
    } else {
        obj.style.display = 'none';
        btnObj.src = btnObj.src.replace(/btn.png/,'btn_off.png');
    }
    var expire = new Date();
    expire.setTime(expire.getTime()+ (7000 * 24 * 3600000));
    xSetCookie('XEMN', obj.style.display, expire);
}

// 캐시파일 모두 재 생성
function doRecompileCacheFile() {
    exec_xml("admin","procAdminRecompileCacheFile", new Array(), completeMessage);
}


