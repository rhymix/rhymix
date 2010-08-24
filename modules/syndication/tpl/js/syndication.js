function insertSelectedModules(id, module_srl, mid, browser_title) {
    var sel_obj = xGetElementById('_'+id);
    for(var i=0;i<sel_obj.options.length;i++) if(sel_obj.options[i].value==module_srl) return;
    var opt = new Option(browser_title+' ('+mid+')', module_srl, false, false);
    sel_obj.options[sel_obj.options.length] = opt;
    if(sel_obj.options.length>8) sel_obj.size = sel_obj.options.length;

    doSyncExceptModules(id);
}

function removeExceptModule(id) {
    var sel_obj = xGetElementById('_'+id);
    sel_obj.remove(sel_obj.selectedIndex);
    if(sel_obj.options.length) sel_obj.selectedIndex = sel_obj.options.length-1;
    doSyncExceptModules(id);
}

function doSyncExceptModules(id) {
    var selected_module_srls = new Array();
    var sel_obj = xGetElementById('_'+id);
    for(var i=0;i<sel_obj.options.length;i++) {
        selected_module_srls.push(sel_obj.options[i].value);
    }
    xGetElementById(id).value = selected_module_srls.join(',');
}
