/* 개별 쪽지 삭제 */
function doDeleteMessage(message_srl) {
	if(!message_srl) return;
	if(!confirm(confirm_delete_msg)) return;

	var params = new Array();
	params['message_srl'] = message_srl;
	exec_xml('communication', 'procCommunicationDeleteMessage', params, completeDeleteMessage);
}

function completeDeleteMessage(ret_obj) {
	alert(ret_obj['message']);
	location.href = current_url.setQuery('message_srl','');
}

function mergeContents()
{
	var $form = jQuery('#fo_comm');
	var content = $form.find('textarea[name=new_content]').val() + $form.find('input[name=source_content]').val();
	$form.find('input[name=content]').val(content);
	$form.submit();
}

/* 친구 그룹 삭제 */
function doDeleteFriendGroup() {
	var friend_group_srl = jQuery('#friend_group_list option:selected').val();
	if(!friend_group_srl) return;

	var fo_obj = jQuery('#for_delete_group').get(0);
	fo_obj.friend_group_srl.value = friend_group_srl;

	procFilter(fo_obj, delete_friend_group);
}

function completeDeleteFriendGroup(ret_obj) {
	alert(ret_obj['message']);
	location.href = current_url.setQuery('friend_group_srl','');
}

/* 친구 그룹의 이름 변경 */
function doRenameFriendGroup() {
	var friend_group_srl = jQuery('#friend_group_list option:selected').val();
	if(!friend_group_srl) return;

	popopen("./?module=communication&act=dispCommunicationAddFriendGroup&friend_group_srl="+friend_group_srl);
}

/* 친구 그룹 이동 */
function doMoveFriend() {
	var fo_obj = jQuery('#fo_friend_list').get(0);
	procFilter(fo_obj, move_friend);
}

/* 친구 그룹 선택 */
function doJumpFriendGroup() {
	var sel_val = jQuery('#jumpMenu option:selected').val();
	location.href = current_url.setQuery('friend_group_srl', sel_val);
}

jQuery(function($){
	$('.__submit_group button[type=submit]').click(function(e){
		var sel_val = $('input[name="friend_srl_list[]"]:checked').length;
		if(sel_val == 0)
		{
			e.preventDefault();
			return false;
		}
	});
});
