/**
 * @file   modules/poll/js/poll_admin.js
 * @author NHN (developers@xpressengine.com)
 * @brief  poll 모듈의 관리자용 javascript
 **/

/* 위젯 코드 생성시 스킨을 고르면 컬러셋의 정보를 표시 */
function doDisplaySkinColorset(sel, colorset) {
    var skin = sel.options[sel.selectedIndex].value;

    var params = new Array();
    params["skin"] = skin;
    params["colorset"] = colorset;

    var response_tags = new Array("error","message","colorset_list");

    exec_xml("poll", "getPollGetColorsetList", params, completeGetSkinColorset, response_tags, params);
}

/* 서버에서 받아온 컬러셋을 표시 */
function completeGetSkinColorset(ret_obj, response_tags, params, fo_obj) {
    var sel = get_by_id("fo_poll").poll_colorset;
    var length = sel.options.length;
    var selected_colorset = params["colorset"];
    for(var i=0;i<length;i++) sel.remove(0);

    var colorset_list = ret_obj["colorset_list"].split("\n");
    var selected_index = 0;
    for(var i=0;i<colorset_list.length;i++) {
        var tmp = colorset_list[i].split("|@|");
        if(selected_colorset && selected_colorset==tmp[0]) selected_index = i;
        var opt = new Option(tmp[1], tmp[0], false, false);
        sel.options.add(opt);
    }

    sel.selectedIndex = selected_index;
}

/* 관리자 페이지에서 선택된 설문조사 원본글로 이동하는 함수 */
function doMovePoll(poll_srl, upload_target_srl) {

    var params = new Array();
    params['poll_srl'] = poll_srl;
    params['upload_target_srl'] = upload_target_srl;

    var response_tags = new Array('error','message','document_srl','comment_srl');
    exec_xml('poll','getPollAdminTarget', params, completeMovePoll, response_tags);
}

function completeMovePoll(ret_obj, response_tags) {
    var document_srl = ret_obj['document_srl'];
    var comment_srl = ret_obj['comment_srl'];
    var url = request_uri.setQuery('document_srl', document_srl);
    if(comment_srl) url = url+'#comment_'+comment_srl;
    winopen(url, 'pollTarget');
}

function getPollList()
{
	var pollListTable = jQuery('#pollListTable');
	var cartList = [];
	pollListTable.find(':checkbox[name=cart]').each(function(){
		if(this.checked) cartList.push(this.value); 
	});

    var params = new Array();
    var response_tags = ['error','message', 'poll_list'];
	params["poll_srls"] = cartList.join(",");

    exec_xml('poll','procPollGetList',params, completeGetPollList, response_tags);
}

function completeGetPollList(ret_obj, response_tags)
{
	var htmlListBuffer = '';

	if(ret_obj['poll_list'] == null)
	{
		htmlListBuffer = '<tr>' +
							'<td colspan="3" style="text-align:center;">'+ret_obj['message']+'</td>' +
						'</tr>';
	}
	else
	{
		var poll_list = ret_obj['poll_list']['item'];
		if(!jQuery.isArray(poll_list)) poll_list = [poll_list];
		for(var x in poll_list)
		{
			var objPoll = poll_list[x];
			htmlListBuffer += '<tr>' +
								'<td class="title">'+objPoll.title+'</td>' +
								'<td class="nowr">'+objPoll.poll_count+'</td>' +
								'<td class="nowr">'+objPoll.nick_name+'</td>' +
							'</tr>' +
							'<input type="hidden" name="cart[]" value="'+objPoll.poll_index_srl+'" />';
		}
		jQuery('#selectedPollCount').html(poll_list.length);
	}
	jQuery('#pollManageListTable>tbody').html(htmlListBuffer);
}

function checkSearch(form)
{
	if(form.search_target.value == '')
	{
		alert(xe.lang.msg_empty_search_target);
		return false;
	}
	if(form.search_keyword.value == '')
	{
		alert(xe.lang.msg_empty_search_keyword);
		return false;
	}
}
