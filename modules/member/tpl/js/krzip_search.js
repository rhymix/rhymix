/* 한국 우편 번호 관련 */
function doHideKrZipList(column_name) {
        var zone_list_obj = xGetElementById('zone_address_list_'+column_name);
        var zone_search_obj = xGetElementById('zone_address_search_'+column_name);
        var zone_addr1_obj = xGetElementById('zone_address_1_'+column_name);
        var addr1_obj = xGetElementById('fo_insert_member')[column_name][0];
        var field_obj = xGetElementById('fo_insert_member')['_tmp_address_search_'+column_name];

        zone_addr1_obj.style.display = 'none';
        zone_list_obj.style.display = 'none';
        zone_search_obj.style.display = 'inline';
        addr1_obj.value = '';
        field_obj.focus();
}

function doSelectKrZip(column_name) {
        var zone_list_obj = xGetElementById('zone_address_list_'+column_name);
        var zone_search_obj = xGetElementById('zone_address_search_'+column_name);
        var zone_addr1_obj = xGetElementById('zone_address_1_'+column_name);
        var sel_obj = xGetElementById('fo_insert_member')['_tmp_address_list_'+column_name];
        var value = sel_obj.options[sel_obj.selectedIndex].value;
        var addr1_obj = xGetElementById('fo_insert_member')[column_name][0];
        var addr2_obj = xGetElementById('fo_insert_member')[column_name][1];
        addr1_obj.value = value;
        zone_search_obj.style.display = 'none';
        zone_list_obj.style.display = 'none';
        zone_addr1_obj.style.display = 'inline';
        addr2_obj.focus();
}

function doSearchKrZip(column_name) {
        var field_obj = xGetElementById('fo_insert_member')['_tmp_address_search_'+column_name];
        var addr = field_obj.value;
        if(!addr) return;

        var params = new Array();
        params['addr'] = addr;
        params['column_name'] = column_name;

        var response_tags = new Array('error','message','address_list');
        exec_xml('krzip', 'getKrzipCodeList', params, completeSearchKrZip, response_tags, params);
}

function completeSearchKrZip(ret_obj, response_tags, callback_args) {
        if(!ret_obj['address_list']) {
                alert(alert_msg['address']);
                return;
        }
        var address_list = ret_obj['address_list'].split("\n");
        var column_name = callback_args['column_name'];

        var zone_list_obj = xGetElementById('zone_address_list_'+column_name);
        var zone_search_obj = xGetElementById('zone_address_search_'+column_name);
        var zone_addr1_obj = xGetElementById('zone_address_1_'+column_name);
        var sel_obj = xGetElementById('fo_insert_member')['_tmp_address_list_'+column_name];

        for(var i=0;i<address_list.length;i++) {
                var opt = new Option(address_list[i],address_list[i],false,false);
                sel_obj.options[i] = opt;
        }

        for(var i=address_list.length-1;i<sel_obj.options.length;i++) {
                sel_obj.remove(i);
        }

        sel_obj.selectedIndex = 0;

        zone_search_obj.style.display = 'none';
        zone_addr1_obj.style.display = 'none';
        zone_list_obj.style.display = 'inline';
}
