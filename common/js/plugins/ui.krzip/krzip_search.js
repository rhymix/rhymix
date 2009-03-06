function doSearchKrZip(obj, column_name) {
    var fo_obj = obj;
    while(fo_obj) {
        if(fo_obj.nodeName == 'FORM') break;
        fo_obj = fo_obj.parentNode;
    }
    if(fo_obj.nodeName != 'FORM') return;

    var field_obj = fo_obj['addr_search_'+column_name];
    if(!field_obj) return;

    var addr = field_obj.value;
    if(!addr) return;

    var params = new Array();
    params['addr'] = addr;
    params['column_name'] = column_name;
    var response_tags = new Array('error','message','address_list');
    exec_xml('krzip', 'getKrzipCodeList', params, completeSearchKrZip, response_tags, params, fo_obj);
}

function completeSearchKrZip(ret_obj, response_tags, callback_args, fo_obj) {
    if(!ret_obj['address_list']) {
            alert(alert_msg['address']);
            return;
    }
    var address_list = ret_obj['address_list'].split("\n");
    var column_name = callback_args['column_name'];

    var zone_list_obj = xGetElementById('addr_list_'+column_name);
    if(!zone_list_obj) return;

    var zone_search_obj = xGetElementById('addr_search_'+column_name);
    if(!zone_search_obj) return;

    var sel_obj = fo_obj['addr_list_'+column_name];
    if(!sel_obj) return;

    for(var i=0;i<sel_obj.length;i++) {
        sel_obj.remove(0);
    }
    for(var i=0;i<address_list.length;i++) {
        if(!address_list[i]) continue;
        var opt = new Option(address_list[i],address_list[i],false,false);
        sel_obj.options[sel_obj.options.length] = opt;
    }

    sel_obj.selectedIndex = 0;

    zone_search_obj.style.display = 'none';
    zone_list_obj.style.display = 'block';
}

function doHideKrZipList(obj, column_name) {
    var fo_obj = obj;
    while(fo_obj) {
        if(fo_obj.nodeName == 'FORM') break;
        fo_obj = fo_obj.parentNode;
    }

    if(fo_obj.nodeName != 'FORM') return;

    var zone_list_obj = xGetElementById('addr_list_'+column_name);
    if(!zone_list_obj) return;

    var zone_search_obj = xGetElementById('addr_search_'+column_name);
    if(!zone_search_obj) return;

    zone_list_obj.style.display = 'none';
    zone_search_obj.style.display = 'block';

    fo_obj['addr_search_'+column_name].focus();
}

function doSelectKrZip(obj, column_name) {
    var fo_obj = obj;
    while(fo_obj) {
        if(fo_obj.nodeName == 'FORM') break;
        fo_obj = fo_obj.parentNode;
    }

    if(fo_obj.nodeName != 'FORM') return;

    var zone_list_obj = xGetElementById('addr_list_'+column_name);
    if(!zone_list_obj) return;

    var zone_search_obj = xGetElementById('addr_search_'+column_name);
    if(!zone_search_obj) return;

    var zone_searched_obj = xGetElementById('addr_searched_'+column_name);
    if(!zone_searched_obj) return;

    var sel_obj = fo_obj['addr_list_'+column_name];
    if(!sel_obj) return;

    var address = sel_obj.options[sel_obj.selectedIndex].value;
    fo_obj[column_name][0].value = address;

    zone_searched_obj.style.display = 'block';
    zone_list_obj.style.display = 'none';
    zone_search_obj.style.display = 'none';

    fo_obj[column_name][1].focus();
}

function doShowKrZipSearch(obj, column_name) {
    var zone_list_obj = xGetElementById('addr_list_'+column_name);
    if(!zone_list_obj) return;

    var zone_search_obj = xGetElementById('addr_search_'+column_name);
    if(!zone_search_obj) return;

    var zone_searched_obj = xGetElementById('addr_searched_'+column_name);
    if(!zone_searched_obj) return;

    zone_searched_obj.style.display = 'none';
    zone_list_obj.style.display = 'none';
    zone_search_obj.style.display = 'block';
}
