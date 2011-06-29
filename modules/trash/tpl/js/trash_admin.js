/**
 * @file   modules/trash/js/trash_admin.js
 * @author NHN (developers@xpressengine.com)
 * @brief  trash 모듈의 관리자용 javascript
 **/


/* 휴지통 비우기 후 */
function completeEmptyTrash(ret_obj) {
    var error = ret_obj['error'];
    var message = ret_obj['message'];

	alert(message);
	if(error == '0') window.location.reload();
}

function goRestore(trash_srl)
{
	if(confirm(confirm_restore_msg))
	{
		var params = {'trash_srl':trash_srl};
		exec_xml('admin', 'procTrashAdminRestore', params, completeRestore);
	}
}

function completeRestore(ret_obj)
{
    var error = ret_obj['error'];
    var message = ret_obj['message'];

	alert(message);
	if(error == '0') window.location.reload();
}
