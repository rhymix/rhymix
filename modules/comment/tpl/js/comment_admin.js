
function doCancelDeclare() {
    var comment_srl = new Array();
    jQuery('#fo_list input[name=cart]:checked').each(function() {
        comment_srl[comment_srl.length] = jQuery(this).val();
    });

    if(comment_srl.length<1) return;

    var params = new Array();
    params['comment_srl'] = comment_srl.join(',');

    exec_xml('comment','procCommentAdminCancelDeclare', params, function() { location.reload(); });
}

function insertSelectedModule(id, module_srl, mid, browser_title) {
    location.href = current_url.setQuery('module_srl',module_srl);
}

function addCart(comment_srl) {
    var params = new Array();
    var response_tags = ['error','message'];
    var comment_srl = new Array();
    jQuery('#fo_list input[name=cart]:checked').each(function() {
        comment_srl[comment_srl.length] = jQuery(this).val();
    });
    params['comment_srl'] = comment_srl.join(',');

    exec_xml('comment','procCommentAdminAddCart',params, completeAddCart, response_tags);
}

function completeAddCart(ret_obj, response_tags)
{
}

function getCommentList()
{
    var params = new Array();
    var response_tags = ['error','message', 'comment_list'];

    exec_xml('comment','procCommentGetList',params, completeGetCommentList, response_tags);
}

function completeGetCommentList(ret_obj, response_tags)
{
	var comment_list = ret_obj['comment_list']['item'];
	console.log(comment_list);
	/*var htmlListBuffer = '';
	var statusNameList = {"PUBLIC":"Public", "SECRET":"Secret", "PRIVATE":"Private", "TEMP":"Temp"};

	for(var x in comment_list)
	{
		var objDocument = comment_list[x];
		htmlListBuffer += '<tr>' +
							'<td class="title">'+ objDocument.variables.title +'</td>' +
							'<td>'+ objDocument.variables.nick_name +'</td>' +
							'<td>'+ statusNameList[objDocument.variables.status] +'</td>' +
							'<td><input type="checkbox" /></td>' +
						'</tr>';
	}
	jQuery('#documentManageListTable>tbody').html(htmlListBuffer);*/
}
