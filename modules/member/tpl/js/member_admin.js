/* 사용자 추가 */
function completeInsert(ret_obj) {
    var error = ret_obj['error'];
    var message = ret_obj['message'];
    var member_srl = ret_obj['member_srl'];
    var page = ret_obj['page'];

    alert(message);

    var url = current_url.setQuery('act','dispMemberAdminInfo').setQuery('member_srl',member_srl);
    if(page) url = url.setQuery('page', page);

    location.href = url;
}

/* 사용자 삭제 */
function completeDelete(ret_obj) {
    var error = ret_obj['error'];
    var message = ret_obj['message'];
    var page = ret_obj['page'];

    alert(message);

    var url = current_url.setQuery('act','dispMemberAdminList');

    location.href = url;
}

/* 그룹 추가 */
function completeInsertGroup(ret_obj) {
    var error = ret_obj['error'];
    var message = ret_obj['message'];
    var page = ret_obj['page'];

    alert(message);

    var url = current_url.setQuery('act','dispMemberAdminGroupList');

    location.href = url;
}

/* 그룹 관련 작업들 */
function doUpdateGroup(group_srl, mode, message) {
    if(typeof(message)!='undefined'&&!confirm(message)) return;

    var fo_obj = xGetElementById('fo_group_info');
    fo_obj.group_srl.value = group_srl;
    fo_obj.mode.value = mode;
    procFilter(fo_obj, update_group);
}

function completeUpdateGroup(ret_obj) {
    var page = ret_obj['page'];
    var url = current_url.setQuery('act','dispMemberAdminGroupList');
    location.href = current_url.setQuery('group_srl','');
}


/* 금지아이디 추가 */
function completeInsertDeniedID(ret_obj) {
    var error = ret_obj['error'];
    var message = ret_obj['message'];
    var page = ret_obj['page'];

    alert(message);

    var url = current_url.setQuery('act','dispMemberAdminDeniedIDList');
    location.href = url;
}

/* 금지아이디 관련 작업들 */
function doUpdateDeniedID(user_id, mode, message) {
    if(typeof(message)!='undefined'&&!confirm(message)) return;

    var fo_obj = xGetElementById('fo_denied_id_info');
    fo_obj.user_id.value = user_id;
    fo_obj.mode.value = mode;
    procFilter(fo_obj, update_denied_id);
}

/* 가입폼 관련 작업들 */
function doUpdateJoinForm(member_join_form_srl, mode, message) {
    if(typeof(message)!='undefined'&&!confirm(message)) return;

    var fo_obj = xGetElementById('fo_join_form_info');
    fo_obj.member_join_form_srl.value = member_join_form_srl;
    fo_obj.mode.value = mode;

    procFilter(fo_obj, update_member_join_form);
}

function completeUpdateJoinForm(ret_obj) {
    var error = ret_obj['error'];
    var message = ret_obj['message'];
    var page = ret_obj['page'];

    alert(message);

    var url = current_url.setQuery('act','dispMemberAdminJoinFormList');

    location.href = url;
}

/* 가입폼 추가 */
function completeInsertJoinForm(ret_obj) {
    var error = ret_obj['error'];
    var message = ret_obj['message'];
    var page = ret_obj['page'];

    alert(message);

    var url = current_url.setQuery('act','dispMemberAdminJoinFormList');

    location.href = url;
}

/* 가입폼의 기본 값 관리 */
function doShowJoinFormValue(sel_obj) {
    var val = sel_obj.options[sel_obj.selectedIndex].value;
    switch(val) {
        case 'checkbox' :
        case 'select' :
                xGetElementById('zone_default_value').style.display = 'block';
            break;
        default :
                xGetElementById('zone_default_value').style.display = 'none';
            break;
    }
}

function doEditDefaultValue(obj, cmd) {
    var listup_obj = xGetElementById('default_value_listup');
    var item_obj = xGetElementById('default_value_item');
    var idx = listup_obj.selectedIndex;
    var lng = listup_obj.options.length;
    var val = item_obj.value;
    switch(cmd) {
        case 'insert' :
                if(!val) return;
                var opt = new Option(val, val, false, true);
                listup_obj.options[listup_obj.length] = opt;
                item_obj.value = '';
                item_obj.focus();
            break;
        case 'up' :
                if(lng < 2 || idx<1) return;

                var value1 = listup_obj.options[idx].value;
                var value2 = listup_obj.options[idx-1].value;
                listup_obj.options[idx] = new Option(value2,value2,false,false);
                listup_obj.options[idx-1] = new Option(value1,value1,false,true);
            break;
        case 'down' :
                if(lng < 2 || idx == lng-1) return;

                var value1 = listup_obj.options[idx].value;
                var value2 = listup_obj.options[idx+1].value;
                listup_obj.options[idx] = new Option(value2,value2,false,false);
                listup_obj.options[idx+1] = new Option(value1,value1,false,true);
            break;
        case 'delete' :
                listup_obj.remove(idx);
                if(idx==0) listup_obj.selectedIndex = 0;
                else listup_obj.selectedIndex = idx-1;
            break;
    }

    var value_list = new Array();
    for(var i=0;i<listup_obj.options.length;i++) {
        value_list[value_list.length] = listup_obj.options[i].value;
    }

    xGetElementById('fo_join_form').default_value.value = value_list.join('|@|');
}

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
    exec_xml('krzip', 'getZipCodeList', params, completeSearchKrZip, response_tags, params);
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


/* 프로필 이미지, 이미지 이름, 마크 삭제 */
function doDeleteProfileImage(member_srl) {
    var fo_obj = xGetElementById("fo_image");
    fo_obj.member_srl.value = member_srl;
    procFilter(fo_obj, delete_profile_image);
}

function doDeleteImageName(member_srl) {
    var fo_obj = xGetElementById("fo_image");
    fo_obj.member_srl.value = member_srl;
    procFilter(fo_obj, delete_image_name);
}

function doDeleteImageMark(member_srl) {
    var fo_obj = xGetElementById("fo_image");
    fo_obj.member_srl.value = member_srl;
    procFilter(fo_obj, delete_image_mark);
}


/* 멤버 스킨 컬러셋 구해옴 */
function doGetSkinColorset(skin) {
    var params = new Array();
    params['skin'] = skin;

    var response_tags = new Array('error','message','tpl');
    exec_xml('member', 'getMemberAdminColorset', params, doDisplaySkinColorset, response_tags);
}

function doDisplaySkinColorset(ret_obj) {
    var tpl = ret_obj["tpl"];
    var old_height = xHeight("member_colorset");
    xInnerHtml("member_colorset", tpl);
    var new_height = xHeight("member_colorset");
    if(typeof(fixAdminLayoutFooter)=="function") fixAdminLayoutFooter(new_height - old_height);
}
