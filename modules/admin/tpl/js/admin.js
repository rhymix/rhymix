/**
 * @file   admin.js
 * @author zero (zero@nzeo.com)
 * @brief  admin 모듈의 javascript
 **/

// 캐시파일 모두 재 생성
function doRecompileCacheFile() {
    exec_xml("admin","procAdminRecompileCacheFile", new Array(), completeMessage);
}

// 모듈 목록 오픈
function toggleModuleMenu(category) {
    var obj = xGetElementById('module_'+category);
    if(obj.className == 'open') obj.className = '';
    else obj.className = 'open';
}

// 메인 모듈/ 애드온 토글
function toggleModuleAddon(target) {
    if(target == 'module') {
        xGetElementById('moduleOn').className = 'on';
        xGetElementById('xeModules').style.display = 'block';
        xGetElementById('addonOn').className = '';
        xGetElementById('xeAddons').style.display = 'none';
    } else {
        xGetElementById('addonOn').className = 'on';
        xGetElementById('xeAddons').style.display = 'block';
        xGetElementById('moduleOn').className = '';
        xGetElementById('xeModules').style.display = 'none';
    }
}

// 언어 목록 toggle
function toggleAdminLang() {
    var obj = xGetElementById("adminLang");
    if(!obj) return;
    if(obj.style.display == 'block') obj.style.display = 'none';
    else obj.style.display = 'block';
}


jQuery(function(){
    jQuery("table.rowTable tr").attr('class','').filter(":nth-child(even)").attr('class','bg1');
});


// 로그아웃
function doAdminLogout() {
    exec_xml('admin','procAdminLogout',new Array(), function() { location.reload(); });
}
