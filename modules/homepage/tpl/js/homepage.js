function completeInsertHomepage(ret_obj) {
    var site_srl = ret_obj['site_srl'];
    location.href = current_url.setQuery('site_srl',site_srl).setQuery('act','dispHomepageAdminSetup');
}

function doHomepageInsertAdmin() {
    var fo_obj = xGetElementById("fo_homepage");
    var sel_obj = fo_obj.admin_list;
    var admin_id = fo_obj.admin_id.value;
    if(!admin_id) return;

    var opt = new Option(admin_id,admin_id,true,true);
    sel_obj.options[sel_obj.options.length] = opt;

    fo_obj.admin_id.value = '';
    sel_obj.size = sel_obj.options.length;
    sel_obj.selectedIndex = -1;
}

function doHomepageDeleteAdmin() {
    var fo_obj = xGetElementById("fo_homepage");
    var sel_obj = fo_obj.admin_list;
    sel_obj.remove(sel_obj.selectedIndex);

    sel_obj.size = sel_obj.options.length;
    sel_obj.selectedIndex = -1;
}

function doUpdateHomepage(fo_obj, func) {
    var sel_obj = fo_obj.admin_list;
    var arr = new Array();
    for(var i=0;i<sel_obj.options.length;i++) {
        arr[arr.length] = sel_obj.options[i].value;
    }
    fo_obj.homepage_admin.value = arr.join(',');
    procFilter(fo_obj, func);
    return false;

}

function completeUpdateHomepage(ret_obj) {
    alert(ret_obj['message']);
    location.reload();
}

function completeDeleteHomepage(ret_obj) {
    alert(ret_obj['message']);
    location.href = current_url.setQuery('act','dispHomepageAdminContent').setQuery('site_srl','');
}
