/* 쪽지 발송 */
function completeSendMessage(ret_obj) {
    alert(ret_obj['message']);
    window.close();
}

function doSendMessage(member_srl, message_srl) {
    if(typeof(message_srl)=='undefined') message_srl = 0;
    var url = current_url.setQuery('module','communication').setQuery('act','dispCommunicationSendMessage').setQuery('receiver_srl',member_srl).setQuery('message_srl',message_srl);
    popopen(url, 'sendMessage');
}

/* 쪽지 모두 선택 */
function doCheckAll(obj, fo_id) {
    var fo_obj = xGetElementById(fo_id);
    for(var i=0; i<fo_obj.length; i++) {
        if(fo_obj[i].type == "checkbox" && fo_obj[i] != obj) fo_obj[i].checked = obj.checked;
    }
}

/* 개별 쪽지 삭제 */
function doDeleteMessage(message_srl) {
    if(!message_srl) return;

    var params = new Array();
    params['message_srl'] = message_srl;
    exec_xml('communication', 'procCommunicationDeleteMessage', params, completeDeleteMessage);
}

function completeDeleteMessage(ret_obj) {
    alert(ret_obj['message']);
    location.href = current_url.setQuery('message_srl','');
}

/* 개별 쪽지 보관 */
function doStoreMessage(message_srl) {
    if(!message_srl) return;

    var params = new Array();
    params['message_srl'] = message_srl;
    exec_xml('communication', 'procCommunicationStoreMessage', params, completeStoreMessage);
}

function completeStoreMessage(ret_obj) {
    alert(ret_obj['message']);
    location.href = current_url.setQuery('message_srl','');
}

/* 친구 추가 후 */
function completeAddFriend(ret_obj) {
    alert(ret_obj['message']);
    var member_srl = ret_obj['member_srl'];
    if(opener && opener.loaded_member_menu_list) {
        opener.loaded_member_menu_list[ret_obj['member_srl']] = '';
    }
    window.close();
}

/* 친구 그룹 추가 후 */
function completeAddFriendGroup(ret_obj) {
    alert(ret_obj['message']);
    if(opener) opener.location.href = opener.location.href;
    window.close();
}

/* 친구 그룹 삭제 */
function doDeleteFriendGroup() {
    var obj = xGetElementById('friend_group_list');
    if(obj.options.length<1) return;
    var friend_group_srl = obj.options[obj.selectedIndex].value;
    var fo_obj = xGetElementById('for_delete_group');
    fo_obj.friend_group_srl.value = friend_group_srl;
    procFilter(fo_obj, delete_friend_group);
}

function completeDeleteFriendGroup(ret_obj) {
    alert(ret_obj['message']);
    location.href = current_url.setQuery('friend_group_srl','');
}

/* 친구 그룹의 이름 변경 */
function doRenameFriendGroup() {
    var obj = xGetElementById('friend_group_list');
    if(obj.options.length<1) return;
    var friend_group_srl = obj.options[obj.selectedIndex].value;
    popopen("./?module=communication&act=dispCommunicationAddFriendGroup&friend_group_srl="+friend_group_srl);
}

/* 친구 그룹 이동 */
function doMoveFriend() {
    var fo_obj = xGetElementById('fo_friend_list');
    procFilter(fo_obj, move_friend);
}

/* 친구 그룹 선택 */
function doJumpFriendGroup() {
    var sel_obj = xGetElementById('jumpMenu');
    var sel_idx = sel_obj.selectedIndex;
    var sel_val = sel_obj.options[sel_idx].value;
    location.href = current_url.setQuery('friend_group_srl', sel_val);
}
