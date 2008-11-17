function completeInsertPlanet(ret_obj, response_tags) {
    alert(ret_obj['message']);
    location.href=current_url.setQuery('module_srl',ret_obj['module_srl']);
}

function completeInsertGrant(ret_obj) {
    var error = ret_obj['error'];
    var message = ret_obj['message'];
    var page = ret_obj['page'];
    var module_srl = ret_obj['module_srl'];

    alert(message);
}

function completeInsertConfig(ret_obj, response_tags) {
    alert(ret_obj['message']);
    location.reload();
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

function completeDeletePlanet(ret_obj) {
    var error = ret_obj['error'];
    var message = ret_obj['message'];
    var page = ret_obj['page'];
    alert(message);

    var url = current_url.setQuery('act','dispPlanetAdminList').setQuery('module_srl','');
    if(page) url = url.setQuery('page',page);
    location.href = url;
}

